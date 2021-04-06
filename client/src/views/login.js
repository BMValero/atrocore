/*
 * This file is part of EspoCRM and/or AtroCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * AtroCore is EspoCRM-based Open Source application.
 * Copyright (C) 2020 AtroCore UG (haftungsbeschränkt).
 *
 * AtroCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AtroCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "AtroCore" word.
 */

Espo.define('views/login', 'view', function (Dep) {

    return Dep.extend({

        template: 'login',

        language: null,

        theme: 'default',

        views: {
            footer: {
                el: 'body > footer',
                view: 'views/site/footer'
            },
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.language = this.getConfig().get('language');
        },

        afterRender: function () {
            let demo = this.getConfig().get('demo') || {"username": "", "password": ""};
            $('#field-userName').val(demo.username);
            $('#field-password').val(demo.password);

            // setup background image
            this.setupBackgroundImage();
        },

        setupBackgroundImage: function () {
            let number = Math.floor(Math.random() * 7) + 1,
                path = this.getBasePath() + `client/img/login/login_cover_${number}.jpg`;

            $('body').css({
                'background-image': 'url(' + `client/img/login/login_cover_${number}.jpg` + ')',
                'background-size': 'cover',
                'background-repeat': 'no-repeat',
                'background-position': 'center',
                'height': '100vh'
            });
            $('body').children('.content').css({
                'height': 'calc(100% - 28px)'
            });
        },

        events: {
            'submit #login-form': function (e) {
                this.login();
                return false;
            },
            'click a[data-action="passwordChangeRequest"]': function (e) {
                this.showPasswordChangeRequest();
            },
            'change select[name="language"]': function (event) {
                this.language = $(event.currentTarget).val();
                if (this.language) {
                    this.ajaxGetRequest('I18n', {locale: this.language}).then((data) => {
                        this.getLanguage().data = data;
                        this.reRender();
                    });
                }
            },
            'change select[name="theme"]': function (event) {
                this.theme = $(event.currentTarget).val();
            }
        },

        data: function () {
            return {
                locales: this.getLocales(),
                themes: this.getThemes(),
                logoSrc: this.getLogoSrc()
            };
        },

        getLogoSrc: function () {
            const companyLogoId = this.getConfig().get('companyLogoId');
            if (!companyLogoId) {
                return this.getBasePath() + 'client/modules/treo-core/img/core_logo_dark.svg';
            }
            return this.getBasePath() + '?entryPoint=LogoImage&id=' + companyLogoId + '&t=' + companyLogoId;
        },

        getLocales() {
            let translatedOptions = Espo.Utils.clone(this.getLanguage().translate('language', 'options') || {});

            return Espo.Utils
                .clone(this.getConfig().get('languageList')).sort((v1, v2) => this.getLanguage().translateOption(v1, 'language').localeCompare(this.getLanguage().translateOption(v2, 'language')))
                .map(item => {
                    return {
                        value: item,
                        label: translatedOptions[item],
                        selected: item === this.language
                    };
                });
        },

        getThemes() {
            let themes = Object.keys(this.getConfig().get('themes') || {}).map(theme => {
                return {
                    name: theme,
                    label: this.translate(theme, 'themes', 'Global')
                }
            });

            themes.unshift({
                name: this.theme,
                label: this.translate('Default', 'labels', 'Global')
            });

            return themes;
        },

        login: function () {
            var userName = $('#field-userName').val();
            var trimmedUserName = userName.trim();
            if (trimmedUserName !== userName) {
                $('#field-userName').val(trimmedUserName);
                userName = trimmedUserName;
            }

            var password = $('#field-password').val();

            var $submit = this.$el.find('#btn-login');

            if (userName == '') {
                var $el = $("#field-userName");

                var message = this.getLanguage().translate('userCantBeEmpty', 'messages', 'User');
                $el.popover({
                    placement: 'bottom',
                    content: message,
                    trigger: 'manual',
                }).popover('show');

                var $cell = $el.closest('.form-group');
                $cell.addClass('has-error');
                this.$el.one('mousedown click', function () {
                    $cell.removeClass('has-error');
                    $el.popover('hide');
                });
                return;
            }

            $submit.addClass('disabled').attr('disabled', 'disabled');

            this.notify('Please wait...');

            $.ajax({
                url: 'App/user',
                headers: {
                    'Basic-Authorization': Base64.encode(userName + ':' + password),
                    'Basic-Authorization-By-Token': false
                },
                data: {
                    language: this.language
                },
                success: function (data) {
                    this.notify(false);

                    if (this.theme !== 'default' && data.preferences.theme !== this.theme) {
                        $.ajax({
                            url: 'Preferences/' + data.user.id,
                            method: 'PUT',
                            headers: {
                                'Basic-Authorization': Base64.encode(userName + ':' + password)
                            },
                            data: JSON.stringify({
                                theme: this.theme
                            })
                        });
                    }

                    this.trigger('login', {
                        auth: {
                            userName: userName,
                            token: data.token
                        },
                        user: data.user,
                        preferences: data.preferences,
                        acl: data.acl,
                        settings: data.settings,
                        appParams: data.appParams
                    });
                }.bind(this),
                error: function (xhr) {
                    $submit.removeClass('disabled').removeAttr('disabled');
                    if (xhr.status == 401) {
                        this.onWrong();
                    }
                }.bind(this),
                login: true,
            });
        },

        onWrong: function () {
            var cell = $('#login .form-group');
            cell.addClass('has-error');
            this.$el.one('mousedown click', function () {
                cell.removeClass('has-error');
            });
            Espo.Ui.error(this.translate('wrongUsernamePasword', 'messages', 'User'));
        },

        showPasswordChangeRequest: function () {
            this.notify('Please wait...');
            this.createView('passwordChangeRequest', 'views/modals/password-change-request', {
                url: window.location.href
            }, function (view) {
                view.render();
                view.notify(false);
            });
        }
    });

});
