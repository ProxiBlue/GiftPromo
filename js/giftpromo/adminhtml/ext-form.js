Object.extend(varienForm.prototype, {
    _submit: function () {
        //console.log(categoryGiftPromo.toQueryString());
        var $form = $(this.formId);
        $form.insert(new Element('input',
            {
                name: 'category_giftpromo',
                value: categoryGiftPromo.toQueryString(),
                type: 'hidden'
            }));
        if (this.submitUrl) {
            $form.action = this.submitUrl;
        }
        $form.submit();
    }
});


