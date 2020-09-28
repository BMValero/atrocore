

Espo.define('treo-core:views/composer/record/panels/log', 'view',
    Dep => Dep.extend({

        template: 'treo-core:composer/record/panels/log',

        collection: null,

        setup() {
            Dep.prototype.setup.call(this);
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            this.getCollectionFactory().create('Note', collection => {
                this.collection = collection;
                this.collection.url = 'Composer/logs';
                this.collection.maxSize = this.getConfig().get('recordsPerPageSmall') || 5;

                this.listenToOnce(this.collection, 'sync', () => {
                    this.createView('list', 'views/stream/record/list', {
                        el: this.options.el + ' .list-container',
                        collection: this.collection,
                        model: null
                    }, function (view) {
                        view.render();
                    });
                });

                this.collection.fetch();
            });
        },

        actionRefresh() {
            if (this.collection) {
                this.collection.fetch();
            }
        }

    })
);