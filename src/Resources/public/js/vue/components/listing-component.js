const listingComponent = Vue.component( 'listing', {
    data: function () {
        return {
            template: '',
            parameters: {}
        }
    },
    methods: {
        fetch: function () {
            this.$http.get( '/catalog-manager/listing/' + this.module, {
                params: this.parameters
            }).then(function ( objResponse ) {
                if ( objResponse.body ) {
                    this.template = objResponse.body.template;
                }
            });
        },
        onChange: function ( shared ) {
            this.parameters = shared;
            this.fetch();
        }
    },
    mounted: function () {
        this.fetch();
    },
    props: {
        module: {
            type: String,
            default: null,
            required: true
        }
    },
    template:
    '<div class="listing-component">' +
        '<div class="listing-component-container" v-html="template"></div>' +
    '</div>'
});

