

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

Espo.define('multilang:views/admin/input-language', 'views/settings/record/edit',
    Dep => Dep.extend({

        layoutName: 'inputLanguage',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.buttonList.push({name: 'updateLayouts', label: 'Update Layouts', style: 'warning'});

            this.listenTo(this.model, 'after:save', function () {
                Espo.Ui.success(this.translate('successAndReload', 'messages', 'Global').replace('{value}', 2));
                setTimeout(function () {
                    window.location.reload(true);
                }, 2000);
            }.bind(this));
        },

        actionSave() {
            const oldList = this.attributes.inputLanguageList || [];
            const newList = this.model.get('inputLanguageList') || [];
            let hasDeletedElements = false;

            if (oldList.length > newList.length) {
                hasDeletedElements = true;
            } else {
                hasDeletedElements = oldList.some(lang => !newList.includes(lang))
            }

            if (hasDeletedElements) {
                Espo.Ui.confirm(this.translate('сonfirmLocaleChanges', 'labels', 'Settings'), {
                    confirmText: this.translate('Apply', 'labels', 'Global'),
                    cancelText: this.translate('Cancel', 'labels', 'Global')
                }, () => {
                    this.save();
                })
            } else {
                this.save();
            }
        },

        actionUpdateLayouts() {
            Espo.Ui.confirm(this.translate('updateLayouts', 'messages', 'Settings'), {
                confirmText: this.translate('Apply', 'labels', 'Global'),
                cancelText: this.translate('Cancel', 'labels', 'Global')
            }, () => {
                this.ajaxPostRequest('Multilang/action/updateLayouts').then(response => {
                    this.notify(this.translate('successAndReload', 'messages', 'Global').replace('{value}', 2), 'success', 3000);
                    setTimeout(function () {
                        window.location.reload(true);
                    }, 2000);
                });
            })
        }
    })
);
