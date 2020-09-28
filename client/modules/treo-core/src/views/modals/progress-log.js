

Espo.define('treo-core:views/modals/progress-log', 'views/modal',
    Dep => Dep.extend({

        template: 'treo-core:modals/progress-log',

        inProgress: false,

        log: null,

        data() {
            return {
                logData: this.log,
                inProgress: this.inProgress
            };
        },

        setup() {
            Dep.prototype.setup.call(this);

            this.setupHeader();
            this.setupButtonList();

            this.setupProgressData(this.options.progressData);
            this.listenTo(this, 'log-updated', progressData => {
                this.setupProgressData(progressData);
                this.reRender();
            });
        },

        setupProgressData(progressData) {
            if (progressData) {
                this.log = progressData.log;
                this.inProgress = progressData.inProgress;
            }
        },

        afterRender() {
            Dep.prototype.afterRender.call(this);

            let spinner = this.$el.find('.spinner');
            this.inProgress ? spinner.removeClass('hidden') : spinner.addClass('hidden');
        },

        setupHeader() {
            this.header = this.translate('progressLog', 'labels', 'Admin');
        },

        setupButtonList() {
            this.buttonList = [
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];
        },

    })
);