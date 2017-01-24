var ModalView = {};
ModalView.Box = Class.create();
Object.extend(ModalView.Box.prototype, {
    initialize: function (id) {
        this.createOverlay();
        this.dialog_box = $(id);
        this.dialog_box.show = this.show.bind(this);
        this.dialog_box.hide = this.hide.bind(this);
        this.parent_element = this.dialog_box.parentNode;
    },

    getDocHeight: function () {
        var D = document;
        return Math.max(
            D.body.scrollHeight, D.documentElement.scrollHeight,
            D.body.offsetHeight, D.documentElement.offsetHeight,
            D.body.clientHeight, D.documentElement.clientHeight
        );
    },

    createOverlay: function () {
        if ($('dialog_overlay')) {
            this.overlay = $('dialog_overlay');
        } else {
            this.overlay = document.createElement('div');
            this.overlay.id = 'dialog_overlay';
            Object.extend(this.overlay.style, {
                position: 'absolute',
                top: 0,
                left: 0,
                zIndex: 90,
                width: '100%',
                backgroundColor: '#000',
                display: 'none'
            });
            document.body.insertBefore(this.overlay, document.body.childNodes[0]);
        }
    },

    moveDialogBox: function (where) {
        Element.remove(this.dialog_box);
        if (where == 'back')
            this.dialog_box = this.parent_element.appendChild(this.dialog_box);
        else
            this.dialog_box = this.overlay.parentNode.insertBefore(this.dialog_box, this.overlay);
    },

    show: function () {
        this.overlay.style.height = this.getDocHeight() + 'px';
        this.moveDialogBox('out');
        this.dialog_box.onclick = this.hide.bind(this);
        new Effect.Appear(this.overlay, {duration: 0.1, from: 0.0, to: 0.3});
        this.dialog_box.style.display = ''
    },

    hide: function () {
        new Effect.Fade(this.overlay, {duration: 0.1});
        this.dialog_box.style.display = 'none';
        this.moveDialogBox('back');
        $A(this.dialog_box.getElementsByTagName('input')).each(function (e) {
            if (e.type != 'submit')e.value = ''
        });
    }

});