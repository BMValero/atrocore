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

Espo.define('views/record/search', 'view', function (Dep) {

    return Dep.extend({

        template: 'record/search',

        scope: null,

        searchManager: null,

        fieldList: ['name'],

        textFilter: '',

        primary: null,

        presetFilterList: null,

        advanced: null,

        pinned: null,

        bool: null,

        disableSavePreset: false,

        textFilterDisabled: false,

        viewModeIconClassMap: {
            list: 'fas fa-align-justify',
            kanban: 'fas fa-grip-horizontal'
        },

        typesWithOneFilter: ['array', 'bool', 'enum', 'multiEnum', 'linkMultiple'],

        hiddenBoolFilterList: [],

        boolFilterData: {},

        data: function () {
            let data = {
                scope: this.scope,
                entityType: this.entityType,
                textFilter: this.textFilter,
                bool: this.bool || {},
                boolFilterList: this.boolFilterList,
                advancedFields: this.getAdvancedDefs(),
                filterDataList: this.getFilterDataList(),
                presetName: this.presetName,
                presetFilterList: this.getPresetFilterList(),
                leftDropdown: this.isLeftDropdown(),
                textFilterDisabled: this.textFilterDisabled,
                viewMode: this.viewMode,
                viewModeDataList: this.viewModeDataList || [],
                hasViewModeSwitcher: this.viewModeList && this.viewModeList.length > 1,
                additionalFilters: this.additionalFilters
            };

            data.boolFilterListLength = 0;
            data.boolFilterListComplex = data.boolFilterList.map(item => {
                let includes = this.hiddenBoolFilterList.includes(item);
                if (!includes) {
                    data.boolFilterListLength++;
                }
                return {name: item, hidden: includes};
            });

            return _.extend({isModalDialog: this.viewMode !== 'list'}, data);
        },

        setup: function () {
            this.additionalFilters = [];
            this.hiddenBoolFilterList = this.options.hiddenBoolFilterList || this.hiddenBoolFilterList;
            this.boolFilterData = this.options.boolFilterData || this.boolFilterData;

            this.entityType = this.collection.name;
            this.scope = this.options.scope || this.entityType;

            this.searchManager = this.options.searchManager;

            this.textFilterDisabled = this.options.textFilterDisabled || this.textFilterDisabled;

            if ('disableSavePreset' in this.options) {
                this.disableSavePreset = this.options.disableSavePreset;
            }

            this.viewMode = this.options.viewMode;
            this.viewModeList = this.options.viewModeList;

            this.addReadyCondition(function () {
                return this.fieldList != null && this.moreFieldList != null;
            }.bind(this));

            this.boolFilterList = Espo.Utils.clone(this.getMetadata().get('clientDefs.' + this.scope + '.boolFilterList') || []).filter(function (item) {
                if (typeof item === 'string') return true;
                item = item || {};
                if (item.inPortalDisabled && this.getUser().isPortal()) return false;
                if (item.isPortalOnly && !this.getUser().isPortal()) return false;
                if (item.accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }
                return true;
            }, this).map(function (item) {
                if (typeof item === 'string') return item;
                item = item || {};
                return item.name;
            }, this);

            var forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType) || [];

            this.moreFieldList = [];
            $.each((this.getMetadata().get(`entityDefs.${this.entityType}.fields`) || {}), (field, row) => {
                if (~forbiddenFieldList.indexOf(field)) return;
                if (row.layoutFilterDisabled) return;
                if (row.layoutFiltersDisabled) return;
                if (row.filterDisabled) return;
                this.moreFieldList.push(field);
            });

            this.presetFilterList = (Espo.Utils.clone(this.getMetadata().get('clientDefs.' + this.scope + '.filterList') || [])).filter(function (item) {
                if (typeof item === 'string') return true;
                item = item || {};
                if (item.inPortalDisabled && this.getUser().isPortal()) return false;
                if (item.isPortalOnly && !this.getUser().isPortal()) return false;
                if (item.accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(item.accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }
                return true;
            }, this);
            ((this.getPreferences().get('presetFilters') || {})[this.scope] || []).forEach(function (item) {
                this.presetFilterList.push(item);
            }, this);

            if (this.getMetadata().get('scopes.' + this.entityType + '.stream')) {
                this.boolFilterList.push('followed');
            }

            this.loadSearchData();

            if (this.presetName) {
                var hasPresetListed = false;
                for (var i in this.presetFilterList) {
                    var item = this.presetFilterList[i] || {};
                    var name = (typeof item === 'string') ? item : item.name;
                    if (name === this.presetName) {
                        hasPresetListed = true;
                        break;
                    }
                }
                if (!hasPresetListed) {
                    this.presetFilterList.push(this.presetName);
                }
            }

            this.model = new this.collection.model();
            this.model.clear();

            this.createFilters();

            this.setupViewModeDataList();
        },

        setupViewModeDataList: function () {
            if (!this.viewModeList) {
                return [];
            }
            var list = [];
            this.viewModeList.forEach(function (item) {
                var o = {
                    name: item,
                    title: this.translate(item, 'listViewModes'),
                    iconClass: this.viewModeIconClassMap[item]
                };
                list.push(o);
            }, this);

            this.viewModeDataList = list;
        },

        setViewMode: function (mode, preventLoop, toTriggerEvent) {
            this.viewMode = mode;

            if (this.isRendered()) {
                this.$el.find('[data-action="switchViewMode"]').removeClass('active');
                this.$el.find('[data-action="switchViewMode"][data-name="'+mode+'"]').addClass('active');
            } else {
                if (this.isBeingRendered() && !preventLoop) {
                    this.once('after:render', function () {
                        this.setViewMode(mode, true);
                    });
                }
            }

            if (toTriggerEvent) {
                this.trigger('change-view-mode', mode);
            }
        },

        isLeftDropdown: function () {
            return this.presetFilterList.length || this.boolFilterList.length || Object.keys(this.advanced || {}).length || this.getAdvancedDefs().length;
        },

        handleLeftDropdownVisibility: function () {
            if (this.isLeftDropdown()) {
                this.$leftDropdown.removeClass('hidden');
            } else {
                this.$leftDropdown.addClass('hidden');
            }
        },

        createFilters: function (callback) {
            var i = 0;
            var count = Object.keys(this.advanced || {}).length;

            if (count == 0) {
                if (typeof callback === 'function') {
                    callback();
                }
            }

            for (var field in this.advanced) {
                this.createFilter(field, this.advanced[field], function () {
                    i++;
                    if (i == count) {
                        if (typeof callback === 'function') {
                            callback();
                        }
                    }
                });
            }
        },

        events: {
            'keyup input[name="textFilter"]': function (e) {
                if (e.keyCode === 13) {
                    this.search();
                }
                this.toggleResetVisibility();
            },
            'focus input[name="textFilter"]': function (e) {
                e.currentTarget.select();
            },
            'click button[data-action="search"]': function (e) {
                this.search();
            },
            'click button[data-action="reset"]': function (e) {
                this.resetFilters();
            },
            'click button[data-action="reset-filter"]': function (e) {
                this.resetFilters();
            },
            'click button[data-action="refresh"]': function (e) {
                this.refresh();
            },
            'click a[data-action="selectPreset"]': function (e) {
                var presetName = $(e.currentTarget).data('name') || null;
                this.selectPreset(presetName);
            },
            'click .advanced-filters-bar a[data-action="showFiltersPanel"]': function (e) {
                this.$advancedFiltersPanel.removeClass('hidden');
            },
            'click .dropdown-menu a[data-action="savePreset"]': function (e) {
                this.createView('savePreset', 'Modals.SaveFilters', {}, function (view) {
                    view.render();
                    this.listenToOnce(view, 'save', function (name) {
                        this.savePreset(name);
                        view.close();

                        this.removeFilters();
                        this.createFilters(function () {
                            this.render();
                        }.bind(this));

                    }, this);
                }.bind(this));
            },
            'click .dropdown-menu a[data-action="removePreset"]': function (e) {
                var id = this.presetName;
                this.confirm(this.translate('confirmation', 'messages'), function () {
                    this.removePreset(id);
                }, this);
            },
            'change .search-row ul.filter-menu input[data-role="boolFilterCheckbox"]': function (e) {
                e.stopPropagation();
                this.search();
                this.manageLabels();
            },
            'click [data-action="switchViewMode"]': function (e) {
                var mode = $(e.currentTarget).data('name');

                if (mode === this.viewMode) return;

                this.setViewMode(mode, false, true);
            },
            'click a[data-action="addFilter"]': function (e) {
                const $target = $(e.currentTarget);
                const name = $target.data('name');

                const nameType = this.model.getFieldType(name.split('-')[0]);
                if (this.typesWithOneFilter.includes(nameType)) {
                    $target.closest('li').addClass('hide');
                }

                this.addFilter(name, {});
            },
            'click .advanced-filters a.remove-filter': function (e) {
                e.stopPropagation();
                e.preventDefault();

                var $target = $(e.currentTarget);
                var name = $target.data('name');

                this.$el.find('ul.filter-list li[data-name="' + name.split('-')[0] + '"]').removeClass('hide');
                var container = this.getView('filter-' + name).$el.closest('div.filter');

                if (!(name in this.pinned) || this.pinned[name] === false) {
                    this.clearView('filter-' + name);
                    container.remove();
                    delete this.advanced[name];
                    this.presetName = this.primary;
                } else {
                    this.getView('filter-' + name).getView('field').clearSearch();
                }

                this.updateAddFilterButton();

                this.fetch();
                this.updateSearch();

                this.manageLabels();
                this.handleLeftDropdownVisibility();
                this.setupOperatorLabels();
                this.toggleResetVisibility();
                this.toggleFilterActionsVisibility();
            },
            'keypress .field input[type="text"]': function (e) {
                if (e.keyCode === 13) {
                    this.search();
                }
            },
            'click .dropdown-submenu > a.add-filter-button': function (e) {
                let a = $(e.currentTarget);
                a.parents('.dropdown-menu').find('> .dropdown-submenu > a:not(.add-filter-button)').next('ul').toggle(false);
                a.next('ul').toggle();
                e.stopPropagation();
                e.preventDefault();
            }
        },

        addFilter(name, params) {
            var nameCount = 1;
            var getLastIndexName = function () {
                if (this.advanced.hasOwnProperty(name + '-' + nameCount)) {
                    nameCount++;
                    getLastIndexName.call(this);
                }
            };
            getLastIndexName.call(this);
            name = name + '-' + nameCount;
            this.advanced[name] = {};
            this.advanced = this.sortAdvanced(this.advanced);

            this.presetName = this.primary;

            this.createFilter(name, params, function (view) {
                view.populateDefaults();
                this.fetch();
                this.updateSearch();
                this.setupOperatorLabels();
            }.bind(this));
            this.updateAddFilterButton();
            this.handleLeftDropdownVisibility();

            this.manageLabels();
            this.toggleResetVisibility();
            this.toggleFilterActionsVisibility();
        },

        toggleResetVisibility() {
            let $textFilter = this.$el.find(`[name="textFilter"]`);
            if ($textFilter.length === 0) {
                return;
            }

            let $reset = this.$el.find(`[data-action="reset"]`);
            if ($textFilter.val().length > 0) {
                $reset.show();
                return;
            }

            if (Object.keys(this.advanced).length > 0) {
                $reset.show();
                return;
            }

            $reset.hide();
        },

        toggleFilterActionsVisibility() {
            let $filterActions = this.$el.find(`.filter-actions`);

            if(!$filterActions.length) {
                return;
            }

            if (Object.keys(this.advanced).length > 0) {
                $filterActions.show();
                return;
            }
            $filterActions.hide();
        },

        sortAdvanced: function (advanced) {
            var result = {};
            Object.keys(advanced).sort(function (item1, item2) {
                return item1.localeCompare(item2, undefined, {numeric: true});
            }).forEach(function (item) {
                result[item] = advanced[item];
            }.bind(this));
            return result;
        },

        refresh: function () {
            this.notify('Loading...');
            this.collection.abortLastFetch();
            this.collection.reset();
            this.collection.fetch().then(function () {
                Espo.Ui.notify(false);
            });
        },

        selectPreset: function (presetName, forceClearAdvancedFilters) {
            var wasPreset = !(this.primary == this.presetName);

            this.presetName = presetName;

            var advanced = this.getPresetData();
            this.primary = this.getPrimaryFilterName();

            var isPreset = !(this.primary === this.presetName);

            if (forceClearAdvancedFilters || wasPreset || isPreset || Object.keys(advanced).length) {
                if (Object.keys(this.pinned).length === 0) {
                    this.removeFilters();
                    this.advanced = advanced;
                }
            }

            this.updateSearch();
            this.manageLabels();

            this.createFilters(function () {
                this.render();
            }.bind(this));
            this.updateCollection();
        },

        removeFilters: function () {
            this.$advancedFiltersPanel.empty();
            for (var name in this.advanced) {
                this.clearView('filter-' + name);
            }
        },

        silentResetFilters: function () {
            this.textFilter = '';
            this.presetName = '';

            this.selectPreset(this.presetName, true);
            this.toggleResetVisibility();
            this.toggleFilterActionsVisibility()
        },

        resetFilters: function () {
            this.trigger('reset');
            this.silentResetFilters();
        },

        savePreset(name) {
            let id = 'f' + (Math.floor(Math.random() * 1000001)).toString();

            this.fetch();
            this.updateSearch();

            let presetFilters = this.getPreferences().get('presetFilters') || {};
            if (!(this.scope in presetFilters)) {
                presetFilters[this.scope] = [];
            }

            let data = {
                id: id,
                name: id,
                label: name,
                data: Espo.Utils.cloneDeep(this.advanced),
                primary: this.primary
            };

            presetFilters[this.scope].push(data);

            this.presetFilterList.push(data);

            this.getPreferences().once('sync', () => {
                this.getPreferences().trigger('update');
                this.updateSearch()
            });

            this.getPreferences().save({
                'presetFilters': presetFilters
            }, {patch: true});

            this.presetName = id;
        },

        removePreset: function (id) {
            var presetFilters = this.getPreferences().get('presetFilters') || {};
            if (!(this.scope in presetFilters)) {
                presetFilters[this.scope] = [];
            }

            var list;
            list = presetFilters[this.scope];
            list.forEach(function (item, i) {
                if (item.id == id) {
                    list.splice(i, 1);
                    return;
                }
            }, this);

            list = this.presetFilterList;
            list.forEach(function (item, i) {
                if (item.id == id) {
                    list.splice(i, 1);
                    return;
                }
            }, this);


            this.getPreferences().set('presetFilters', presetFilters);
            this.getPreferences().save({patch: true});
            this.getPreferences().trigger('update');

            this.presetName = this.primary;
            this.advanced = {};

            this.removeFilters();

            this.render();
            this.updateSearch();
            this.updateCollection();
        },

        updateAddFilterButton: function () {
            var $ul = this.$el.find('ul.filter-list');
            if ($ul.children().not('.hide').size() == 0) {
                this.$el.find('a.add-filter-button').addClass('hidden');
            } else {
                this.$el.find('a.add-filter-button').removeClass('hidden');
            }
        },

        afterRender: function () {
            this.$filtersLabel = this.$el.find('.search-row span.filters-label');
            this.$filtersButton = this.$el.find('.search-row button.filters-button');
            this.$leftDropdown = this.$el.find('div.search-row div.left-dropdown');

            this.updateAddFilterButton();

            this.$advancedFiltersBar = this.$el.find('.advanced-filters-bar');
            this.$advancedFiltersPanel = this.$el.find('.advanced-filters');

            this.manageLabels();
            this.setupOperatorLabels();
            this.setupAdvancedFiltersPosition();
            this.toggleResetVisibility();
            this.toggleFilterActionsVisibility();

            const textFilter = this.$el.find('input[name="textFilter"]');

            if (textFilter.length > 0) {
                textFilter.attr('placeholder', this.getTextFilterPlaceholder())

                $(window).on('resize', () => {
                    textFilter.attr('placeholder', this.getTextFilterPlaceholder())
                })
            }
        },

        getTextFilterPlaceholder() {
            let label = '';

            if ($(window).width() >= 768) {
                label = this.getLanguage().translate('textFilterPlaceholder', 'labels');
            } else {
                label = this.getLanguage().translate('mobileTextFilterPlaceholder', 'labels');
            }

            return label;
        },

        toggleSearchFilters(selectedViewType) {
            let $button = this.$el.find('.filters-button');
            if ($button.length === 0) {
                return
            }

            $button.removeClass('disabled');
            if (selectedViewType === 'tree') {
                $button.addClass('disabled');
            }
        },

        getFilterName(filter) {
            let name = '';
            let nextView = this.getView('filter-' + filter);
            if (nextView) {
                if (nextView.options.params.isAttribute) {
                    name = nextView.options.params.label;
                } else {
                    name = this.translate(nextView.generalName, 'fields', this.scope);
                }
            }
            return name;
        },

        setupOperatorLabels() {
            let filters = this.$advancedFiltersPanel.find('.filter');

            let el = $(filters[0]);
            let curLabel = el.find('label.control-label');
            curLabel.text(this.getFilterName(el.data('name')));

            filters.each((index, filter) => {
                if (filters[index + 1]) {
                    let prevFilter = $(filter);
                    let prevName = prevFilter.data('name');
                    let nextFilter = $(filters[index + 1]);
                    let nextName = nextFilter.data('name');
                    let nextLabel = nextFilter.find('label.control-label');
                    if (prevName.split('-')[0] === nextName.split('-')[0]) {
                        nextLabel.text('OR ' + this.getFilterName(nextName));
                    } else {
                        nextLabel.text('AND ' + this.getFilterName(nextName));
                    }
                }
            });
        },

        setupAdvancedFiltersPosition() {
            let searchContainer = $('.page-header .search-container');

            if (searchContainer.has(this.$advancedFiltersPanel).length) {
                let list = $('.list-container');
                let $window = $(window);

                if ($window.outerWidth() >= 768) {
                    this.$advancedFiltersPanel.css({
                        'width': list.outerWidth() + 'px'
                    });
                }

                $window.resize(function () {
                    if ($window.outerWidth() >= 768) {
                        this.$advancedFiltersPanel.css({
                            'width': list.outerWidth() + 'px'
                        });
                    } else {
                        this.$advancedFiltersPanel.css({
                            'width': 'unset'
                        });
                    }
                }.bind(this));
            }
        },

        manageLabels: function () {
            this.$advancedFiltersBar.empty().addClass('hidden');

            this.$el.find('ul.dropdown-menu > li.preset-control').addClass('hidden');

            this.currentFilterLabelList = [];

            this.managePresetFilters();
            this.manageBoolFilters();

            this.$filtersLabel.html(this.currentFilterLabelList.join(', '));
        },

        addLabelHtml: function (label, style, id, noAction) {
            var action = "showFiltersPanel";
            var cursor = 'pointer';
            var tag = 'a';
            if (noAction) {
                action = "NO_ACTION";
                tag = 'span';
                cursor = 'default';
            }

            var barContentHtml = '<'+tag+' href="javascript:" style="cursor: '+cursor+';" class="label label-'+style+'" data-action="'+action+'">' + label + '</'+tag+'>';
            if (id) {
                barContentHtml += ' <a href="javascript:" title="'+this.translate('Remove')+'" class="small" data-action="removePreset" data-id="'+id+'"><span class="fas fa-times"></span></a>';
            }
            barContentHtml = '<span style="margin-right: 10px;">' + barContentHtml + '</span>'

            this.$advancedFiltersBar.append($(barContentHtml));
            this.$advancedFiltersBar.removeClass('hidden');

        },

        managePresetFilters: function () {
            var presetName = this.presetName || null;
            var data = this.getPresetData();
            var primary = this.primary;

            this.$el.find('ul.filter-menu a.preset span').remove();

            var filterLabel = this.translate('All');
            var filterStyle = 'default';

            if (!presetName && primary) {
                presetName = primary;
            }

            if (presetName && presetName != primary) {
                var label = null;
                var style = 'default';
                var id = null;

                this.presetFilterList.forEach(function (item) {
                    if (item.name == presetName) {
                        label = item.label || false;
                        style = item.style || 'default';
                        id = item.id;
                        return;
                    }
                }, this);
                label = label || this.translate(this.presetName, 'presetFilters', this.entityType);

                filterLabel = label;
                filterStyle = style;

                if (id) {
                    this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                    this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').removeClass('hidden');
                }

            } else {
                if (Object.keys(this.advanced).length !== 0) {
                    if (!this.disableSavePreset) {
                        this.$el.find('ul.dropdown-menu > li.divider.preset-control').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.save-preset').removeClass('hidden');
                        this.$el.find('ul.dropdown-menu > li.preset-control.remove-preset').addClass('hidden');

                    }
                }

                if (primary) {
                    var label = this.translate(primary, 'presetFilters', this.entityType);
                    var style = this.getPrimaryFilterStyle();
                    filterLabel = label;
                    filterStyle = style;
                }
            }

            this.currentFilterLabelList.push(filterLabel);

            this.$filtersButton.removeClass('btn-default')
                .removeClass('btn-primary')
                .removeClass('btn-danger')
                .removeClass('btn-success')
                .removeClass('btn-info');
            this.$filtersButton.addClass('btn-' + filterStyle);

            presetName = presetName || '';

            this.$el.find('ul.filter-menu a.preset[data-name="'+presetName+'"]').prepend('<span class="fas fa-check pull-right"></span>');
        },

        manageBoolFilters() {
            (this.boolFilterList || []).forEach(item => {
                if (this.bool[item] && !this.hiddenBoolFilterList.includes(item)) {
                    this.currentFilterLabelList.push(this.translate(item, 'boolFilters', this.entityType));
                }
            });
        },

        search: function () {
            this.fetch();
            this.updateSearch();
            this.updateCollection();
        },

        getFilterDataList: function () {
            var arr = [];
            for (var field in this.advanced) {
                if (this.isFieldExist(field, this.advanced[field])) {
                    arr.push({
                        key: 'filter-' + field,
                        name: field
                    });
                } else {
                    delete this.advanced[field];
                }
            }

            // set to searchManager
            this.searchManager.set(_.extend(this.searchManager.get(), {advanced: this.advanced}));

            return arr;
        },

        isFieldExist(name, filterField) {
            let field = name.split('-').shift();

            return !!this.getMetadata().get(['entityDefs', this.scope, 'fields', field]);
        },

        updateCollection() {
            this.collection.reset();
            this.notify('Please wait...');
            this.listenTo(this.collection, 'sync', function () {
                this.notify(false);
            }.bind(this));
            let where = this.searchManager.getWhere();
            where.forEach(item => {
                if (item.type === 'bool') {
                    let data = {};
                    item.value.forEach(elem => {
                        if (elem in this.boolFilterData) {
                            data[elem] = this.boolFilterData[elem];
                        }
                    });
                    _.extend(item.data, data);
                }
            });

            if (this.options.selectRecordsView) {
                const maxForTree = 200;
                if (this.collection.maxSize === maxForTree) {
                    this.collection.maxSize = this.getConfig().get('recordsPerPage');
                }
                if (this.options.selectRecordsView.getSelectedViewType() === 'tree' && where) {
                    where.forEach(item => {
                        if (item.type && item.type === 'textFilter') {
                            this.collection.maxSize = maxForTree;
                        }
                    });
                }
            }

            this.collection.where = where;
            this.collection.fetch().then(() => Backbone.trigger('after:search', this.collection));
        },

		getPresetFilterList: function () {
			var arr = [];
            this.presetFilterList.forEach(function (item) {
            	if (typeof item == 'string') {
            		item = {name: item};
            	}
            	arr.push(item);
            }, this);
            return arr;
		},

        getPresetData: function () {
            var data = {};
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.presetName) {
                    data = Espo.Utils.clone(item.data || {});
                    return;
                }
            }, this);
            return data;
        },

        getPrimaryFilterName: function () {
            var primaryFilterName = null;
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.presetName) {
                    if (!('data' in item)) {
                        primaryFilterName = item.name;
                    } else if (item.primary) {
                        primaryFilterName = item.primary;
                    }
                    return;
                }
            }, this);
            return primaryFilterName;
        },

        getPrimaryFilterStyle: function () {
            var style = null;
            this.getPresetFilterList().forEach(function (item) {
                if (item.name == this.primary) {
                    style = item.style || 'default';
                    return;
                }
            }, this);
            return style;
        },

        loadSearchData: function () {
            var searchData = this.searchManager.get();
            this.textFilter = searchData.textFilter;

            if ('presetName' in searchData) {
                this.presetName = searchData.presetName;
            }

            var primaryIsSet = false;
            if ('primary' in searchData) {
                this.primary = searchData.primary;
                if (!this.presetName) {
                    this.presetName = this.primary;
                }
                primaryIsSet = true;
            }

            if (this.presetName) {
                this.advanced = _.extend(Espo.Utils.clone(this.getPresetData()), searchData.advanced);
                if (!primaryIsSet) {
                    this.primary = this.getPrimaryFilterName();
                }
            } else {
                this.advanced = Espo.Utils.clone(searchData.advanced);
            }
            this.bool = searchData.bool;
            this.pinned = searchData.pinned;
        },

        createFilter: function (name, params, callback, noRender) {
            params = params || {};

            var rendered = false;
            if (this.isRendered()) {
                rendered = true;
                var div = document.createElement('div');
                div.className = "filter filter-" + name;
                div.setAttribute("data-name", name);
                var nameIndex = name.split('-')[1];
                var beforeFilterName = name.split('-')[0] + '-' + (+nameIndex - 1);
                var beforeFilter = this.$advancedFiltersPanel.find('.filter.filter-' + beforeFilterName + '.col-sm-4.col-md-3')[0];
                var afterFilterName = name.split('-')[0] + '-' + (+nameIndex + 1);
                var afterFilter = this.$advancedFiltersPanel.find('.filter.filter-' + afterFilterName + '.col-sm-4.col-md-3')[0];
                if (beforeFilter) {
                    var nextFilter = beforeFilter.nextElementSibling;
                    if (nextFilter) {
                        this.$advancedFiltersPanel[0].insertBefore(div, beforeFilter.nextElementSibling);
                    } else {
                        this.$advancedFiltersPanel[0].appendChild(div);
                    }
                } else if (afterFilter) {
                    this.$advancedFiltersPanel[0].insertBefore(div, afterFilter);
                } else {
                    this.$advancedFiltersPanel[0].appendChild(div);
                }
            }

            let fieldParams = params.fieldParams || params;

            this.createView('filter-' + name, fieldParams.filterView || 'treo-core:views/search/filter', {
                name: name,
                model: this.model,
                params: fieldParams,
                searchParams: params,
                el: this.options.el + ' .filter[data-name="' + name + '"]',
                pinned: this.pinned[name] || false
            }, function (view) {
                if (typeof callback === 'function') {
                    view.once('after:render', function () {
                        callback(view);
                    });
                }
                if (rendered && !noRender) {
                    view.render();
                }

                this.listenTo(view, 'pin-filter', function (pinned) {
                    if (pinned) {
                        this.pinned[view.name] = pinned;
                    } else {
                        delete this.pinned[view.name];
                    }

                    this.updateSearch();
                });
            }.bind(this));
        },

        fetch: function () {
            this.textFilter = (this.$el.find('input[name="textFilter"]').val() || '').trim();

            this.bool = {};

            this.boolFilterList.forEach(function (name) {
                this.bool[name] = this.$el.find('input[name="' + name + '"]').prop('checked');
            }, this);

            for (var field in this.advanced) {
                var view = this.getView('filter-' + field).getView('field');
                this.advanced[field] = view.fetchSearch();
                view.searchParams = this.advanced[field];
            }
        },

        updateSearch: function () {
            this.searchManager.set({
                textFilter: this.textFilter,
                advanced: this.advanced,
                bool: this.bool,
                presetName: this.presetName,
                primary: this.primary,
                pinned: this.pinned
            });
        },

        getAdvancedDefs: function () {
            var defs = [];
            for (var i in this.moreFieldList) {
                var field = this.moreFieldList[i];
                var fieldType = this.model.getFieldType(field.split('-')[0]);
                var advancedFieldsList = [];
                Object.keys(this.advanced).forEach(function (item) {
                    advancedFieldsList.push(item.split('-')[0]);
                });
                var o = {
                    name: field,
                    label: this.translate(field, 'fields', this.scope),
                    checked: (this.typesWithOneFilter.indexOf(fieldType) > -1 && advancedFieldsList.indexOf(field) > -1),
                };
                defs.push(o);
            }

            defs.sort((a, b) => {
                if (a.label < b.label) {
                    return -1;
                }
                if (a.label > b.label) {
                    return 1;
                }
                return 0;
            })

            return defs;
        },

    });
});

