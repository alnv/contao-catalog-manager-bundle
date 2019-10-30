Vue.directive( 'share', {

    inserted: function (el) {

        console.log(el);
    }
});

const listingComponent = Vue.component( 'listing', {
    data: function () {
        return {
            view: '',
            parameters: {
                order: {}
            }
        }
    },
    methods: {
        fetch: function (strUrl) {
            if (!strUrl) {
                strUrl = '/catalog-manager/listing/' + this.module + '/' + this.page;
            }
            this.$http.get( strUrl, {
                params: this.parameters
            }).then(function ( objResponse ) {
                if ( objResponse.body ) {
                    this.view = objResponse.body.template;
                }
            });
        },
        onChange: function ( shared ) {
            this.parameters = shared;
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
                    self.fetch(this.href);
                });
            }
        },
        collectShareData: function () {
            this.$parent.shared['listingShareData'] = [];
            var arrShares = this.$refs.view.querySelectorAll('*[data-share]');
            for ( var i = 0; i < arrShares.length; i++ ) {
                var objShare = arrShares[i];
                var strShare = objShare.dataset['share'];
                if ( strShare === null || strShare === '' ) {
                    continue;
                }
                if ( this.$parent.shared['listingShareData'].indexOf( strShare ) === -1 ) {
                    this.$parent.shared['listingShareData'].push( strShare );
                }
            }
        }
    },
    updated: function () {
        this.$nextTick(function () {
            this.sortable();
            this.pagination();
            this.collectShareData();
        })
    },
    mounted: function () {
        this.fetch();
    },
    props: {
        module: {
            type: String,
            default: null,
            required: true
        },
        page: {
            type: String,
            default: null,
            required: true
        }
    },
    template:
    '<div class="listing-component" ref="view">' +
        '<transition name="fade">' +
            '<div class="listing-component-container" v-html="view" v-show="view"></div>' +
        '</transition>' +
    '</div>'
});