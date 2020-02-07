const AsyncImageComponent = Vue.component( 'async-image', {
    data: function () {
        return {
            src: null,
            alt: ''
        }
    },
    methods: {
        fetch: function () {
            this.src = '';
            this.alt = '';
            this.$http.post( '/catalog-manager/async-image', {
                id: this.id,
                role: this.role,
                table: this.table
            },{
                emulateJSON: true,
                'Content-Type': 'application/x-www-form-urlencoded'
            }).then(function ( objResponse ) {
                if ( objResponse.body ) {
                    this.src = objResponse.body.src;
                    this.alt = objResponse.body.alt;
                }
            });
        },
        onChange: function () {
            //
        },
        setSticky: function () {
            if ( typeof Sticky === 'undefined' || !this.sticky ) {
                return null;
            }
            this.$el.classList.add('is-sticky');
            if ( this.sticky.hasOwnProperty('marginTop') ) {
                this.$el.setAttribute('data-margin-top', this.sticky['marginTop']);
            }
            new Sticky(this.$el);
        }
    },
    watch: {
        role: function () {
            this.fetch();
        },
        id: function () {
            this.fetch();
        }
    },
    updated: function () {
        this.$nextTick(function () {
            this.setSticky();
        })
    },
    mounted: function () {
        this.fetch();
    },
    props: {
        id: {
            type: String,
            required: true
        },
        role: {
            type: String,
            required: true
        },
        table: {
            type: String,
            required: true
        },
        sticky: {
            type: Object,
            default: null,
            required: false
        }
    },
    template:
        '<div class="async-image-component">' +
            '<slot>' +
                '<div class="ce_image block">' +
                    '<figure v-if="src"><img :src="src" :alt="alt"></figure>' +
                    '<loading v-if="!src"></loading>' +
                '</div>' +
            '</slot>' +
        '</div>'
});