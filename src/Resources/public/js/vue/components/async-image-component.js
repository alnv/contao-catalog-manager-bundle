const AsyncImageComponent = Vue.component( 'async-image', {
    data: function () {
        return {
            src: null,
            alt: ''
        }
    },
    methods: {
        fetch: function () {
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
            if ( this.$parent.shared.item ) {
                var imageId = parseInt( this.$parent.shared.item );
                if ( imageId !== this.id ) {
                    this.id = imageId;
                    this.src = '';
                    this.alt = '';
                    this.fetch();
                }
            }
        }
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
        }
    },
    template:
        '<div class="async-image-component">' +
            '<slot>' +
                '<div class="ce_image block">' +
                    '<figure v-if="src">' +
                        '<img :src="src" :alt="alt">' +
                    '</figure>' +
                    '<loading v-if="!src"></loading>' +
                '</div>' +
            '</slot>' +
        '</div>'
});