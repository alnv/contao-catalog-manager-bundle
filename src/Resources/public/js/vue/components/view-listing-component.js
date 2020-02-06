const viewListingComponent = Vue.component( 'view-listing', {
    data: function () {
        return {
            view: '',
            shareData: [],
            parameters: {
                order: {}
            }
        }
    },
    methods: {
        fetch: function (strUrl) {
            if (!strUrl) {
                strUrl = '/catalog-manager/view-listing/' + this.module + '/' + this.page;
            }
            this.$http.get( strUrl, {
                params: this.parameters
            }).then(function ( objResponse ) {
                if ( objResponse.body && objResponse.ok ) {
                    this.view = objResponse.body.template;
                    this.$parent.clearAlert();
                }
                if ( !objResponse.ok ) {
                    this.$parent.setErrorAlert( '', this );
                }
            });
        },
        onChange: function ( shared ) {
            this.parameters = shared;
            if ( this.view ) {
                this.$parent.setLoadingAlert( '', this );
            }
            this.fetch();
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
        collectShareData: function () {
            this.shareData = [];
            var arrShares = this.$refs.view.querySelectorAll('*[data-share]');
            for ( var i = 0; i < arrShares.length; i++ ) {
                var objShare = arrShares[i];
                var strShare = objShare.dataset['share'];
                if ( strShare === null || strShare === '' ) {
                    continue;
                }
                if ( this.shareData.indexOf( strShare ) === -1 ) {
                    this.shareData.push( strShare );
                }
            }
        }
    },
    watch: {
        shareData : {
            handler: function () {
                this.$parent.shared['listingShareData'] = this.shareData;
                this.$parent.onChange(this);
            },
            deep: true
        }
    },
    updated: function () {
        this.$nextTick(function () {
            this.sortable();
            this.pagination();
            this.setMasonryLayout();
            this.collectShareData();
        })
    },
    mounted: function () {
        if (!this.awaitOnChange) {
            this.fetch();
        }
    },
    props: {
        awaitOnChange: {
            type: Boolean,
            default: false,
            required: true
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
        }
    },
    template:
    '<div class="view-component" ref="view">' +
        '<div class="view-component-container" v-html="view" v-if="view"></div>' +
        '<loading v-if="!view"></loading>' +
    '</div>'
});