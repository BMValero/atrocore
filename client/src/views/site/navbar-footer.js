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
 *
 * This software is not allowed to be used in Russia and Belarus.
 */

Espo.define('views/site/navbar-footer', 'treo-core:views/site/footer', function (Dep) {

    return Dep.extend({

        template: 'site/navbar-footer',

        version: null,

        events: {
            'click span.toggle-icon-up': function (e) {
                $(e.target).addClass('hidden');


                $('.footer-links').removeClass('hidden');
                $('span.toggle-icon-down').removeClass('hidden');
            },
            'click span.toggle-icon-down': function (e) {
                $(e.target).addClass('hidden');
                $('.footer-links').addClass('hidden');

                $('span.toggle-icon-up').removeClass('hidden');
            }
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.ajaxGetRequest('Composer/list').then(function (response) {
                if (response.list) {
                    response.list.forEach(item => {
                        if (!this.version && item.id === 'TreoCore') {
                            this.version = item.currentVersion;
                        }
                    });
                }

                this.wait(false);
            }.bind(this));
        },

        data() {
            return {
                version: this.version
            }
        }

    });

});


