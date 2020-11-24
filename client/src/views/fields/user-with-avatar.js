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

Espo.define('views/fields/user-with-avatar', 'views/fields/user', function (Dep) {

    return Dep.extend({

        listTemplate: 'fields/user-with-avatar/list',

        detailTemplate: 'fields/user-with-avatar/detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.has('ownerUserId') && this.model.get('ownerUserId') === null && this.setDefaultOwnerUser()) {
                this.model.set('ownerUserId', this.getUser().id);
                this.model.set('ownerUserName', this.getUser().get('name'));
            }

            if (this.model.has('assignedUserId') && this.model.get('assignedUserId') === null && this.setDefaultAssignedUser()) {
                this.model.set('assignedUserId', this.getUser().id);
                this.model.set('assignedUserName', this.getUser().get('name'));
            }
        },

        data: function () {
            var o = _.extend({}, Dep.prototype.data.call(this));
            if (this.mode === 'detail') {
                o.avatar = this.getAvatarHtml();
            }
            return o;
        },

        getAvatarHtml: function () {
            return this.getHelper().getAvatarHtml(this.model.get(this.idName), 'small', 14, 'avatar-link');
        },

        setDefaultOwnerUser: function () {
            return true;
        },

        setDefaultAssignedUser: function () {
            return true;
        }
    });
});
