Vue.directive( 'sticky-image', {
    bind: function ($el,binding,vnode) {
        $el.classList.add('is-sticky');
        if ( binding.value.hasOwnProperty('isSticky' ) ) {
            if ( !binding.value.isSticky ) {
                return null;
            }
        }
        if ( binding.value.hasOwnProperty('offset' )) {
            $el.setAttribute('data-margin-top', binding.value.offset);
        }
        new Sticky($el);
    },
    update: function ($el,binding,vnode) {
        //
    }
});