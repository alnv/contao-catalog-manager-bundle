const viewListingComponent = Vue.component('view-listing', {
    data: function () {
        return {
            view: null,
            parameters: {
                reload: 0,
                order: {}
            }
        }
    },
    methods: {
        fetch: function (strUrl) {
            if (!strUrl) {
                strUrl = '/catalog-manager/view-listing/' + this.module + '/' + this.page;
            }
            this.$http.post( strUrl, this.parameters, {
                emulateJSON: true,
                'Content-Type': 'application/x-www-form-urlencoded'
                }).then(function ( objResponse ) {
                if (objResponse.body && objResponse.ok) {
                    this.view = objResponse.body.template;
                    this.$parent.clearAlert();
                    if (objResponse.body.max && this.reload) {
                        this.reload = false;
                    }
                }
                if (!objResponse.ok) {
                    this.$parent.setErrorAlert( '', this );
                }
            }.bind(this));
        },
        onChange: function (shared) {
            this.addSharedParameters(shared);
            if (this.view) {
                this.$parent.setLoadingAlert( '', this );
            }
            this.fetch();
        },
        addSharedParameters: function(shared) {
            for (var name in shared) {
                if (shared.hasOwnProperty(name)) {
                    this.parameters[name] = shared[name];
                }
            }
        },
        sortable: function () {
            var objSortable = this.$refs.view.querySelector('.sortable');
            if ( !objSortable ) {
                return null;
            }
            var arrSortingFields = objSortable.querySelectorAll('[data-sort]');
            if ( !arrSortingFields.length ) {
                return null;
            }
            var self = this;
            for ( var i = 0; i < arrSortingFields.length; i++ ) {
                var objSortField = arrSortingFields[i];
                var strFieldname = objSortField.dataset.sort;
                if ( typeof this.parameters['order'] !== 'undefined' && this.parameters['order'].hasOwnProperty( strFieldname ) ) {
                    objSortField.dataset.order = this.parameters['order'][ strFieldname ]['order'];
                }
                objSortField.classList.remove('desc');
                objSortField.classList.remove('asc');
                if ( objSortField.dataset.order ) {
                    objSortField.classList.add( objSortField.dataset.order );
                }
                objSortField.addEventListener('click', function ( objEvent ) {
                    objEvent.preventDefault();
                    if ( !this.dataset.sort ) {
                        return null;
                    }
                    this.dataset.order = this.dataset.order === 'asc' || !this.dataset.order ? 'desc' : 'asc';
                    if ( typeof self.parameters.order === 'undefined' ) {
                        self.parameters.order = {};
                    }
                    self.parameters.order[ this.dataset.sort ] = {
                        'field': this.dataset.sort,
                        'order': this.dataset.order
                    };
                    this.classList.remove( 'desc' );
                    this.classList.remove( 'asc' );
                    this.classList.add( this.dataset.order );
                    self.fetch();
                });
            }
        },
        pagination: function () {
            var self = this;
            var objPagination = this.$refs.view.querySelector('.pagination');
            if ( !objPagination ) {
                return null;
            }
            var arrLinks = objPagination.querySelectorAll('a');
            for ( var i = 0; i < arrLinks.length; i++ ) {
                arrLinks[i].addEventListener( 'click', function ( objEvent ) {
                    objEvent.preventDefault();
                    self.$parent.setLoadingAlert( '', self );
                    self.fetch(this.href);
                });
            }
        },
        setMasonryLayout: function() {
            if ( typeof Masonry === 'undefined' || !this.masonry ) {
                return null;
            }
            var objOptions = this.masonry.options || {};
            new Masonry(this.$el.querySelector(this.masonry.item), objOptions);
        },
        listReload: function () {
            this.parameters.reload += 1;
            this.$parent.setLoadingAlert( '', this );
            this.fetch();
        },
    },
    updated: function () {
        this.$nextTick(function () {
            this.sortable();
            this.pagination();
            this.setMasonryLayout();
        })
    },
    mounted: function () {
        if (!this.awaitOnChange) {
            if (typeof this.$parent.shared !== 'undefined') {
                this.addSharedParameters(this.$parent.shared);
            }
            this.fetch();
        }
    },
    props: {
        awaitOnChange: {
            type: Boolean,
            default: false,
            required: false
        },
        module: {
            type: String,
            default: null,
            required: true
        },
        page: {
            type: String,
            default: null,
            required: true
        },
        masonry: {
            type: Object,
            default: null,
            required: false
        },
        reload: {
            type: Boolean,
            default: false,
            required: false
        },
        reloadButton: {
            type: String,
            required: false,
            default: 'Mehr laden'
        }
    },
    template:
    '<div class="view-component" ref="view">' +
        '<div class="view-component-container" v-if="view" v-html="view"></div>' +
        '<div v-if="reload && view" class="reload block"><button v-on:click.prevent="listReload">{{ reloadButton }}</button></div>' +
        '<loading v-if="!view"></loading>' +
    '</div>'
});