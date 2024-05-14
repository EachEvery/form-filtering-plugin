(function ($) {

    if($('form').length <= 0) return;
    if($('[type="email"]').length <= 0) return;

    let formType = $('form').find('.gform-body').length > 0 ? 'gforms' : null;
    let errorMessages;
    let nameObj = { first_name: '', last_name: '', first_name_element};
    let vowels = [
        "a",
        "e",
        "i",
        "o",
        "u"
    ];
    let blacklist = [ "test.com", "10minutemail.com", "10minutemail.net" ];
    let errorArray = []

    const _init = async function() {
        response = await fetch(formFilter.list)
        blacklist = await response.json();
        errorMessages = JSON.parse(formFilter.errors)
    }

    const add_error = function(error, el) {        
        if(formType == 'gforms'){
            if(el.parent().find('.ee-ff-validation').length <= 0 && errorArray[el.attr('id')] !== false){
                el.parent().addClass('ee-ff-error')
                errorArray[el.attr('id')] = error;
                el.after(`<div class="ee-ff-validation">${error}</div>`)
            }
        }
    }

    const remove_error = function(el) {
        if(formType == 'gforms'){
            if(errorArray[el.attr('id')]) {
                errorArray[el.attr('id')] = null;
            }
            el.parent().removeClass('ee-ff-error')
            el.parent().find('.ee-ff-validation').remove();
        }
    }

    const handle_email_input_blur = function() {
        
        // Clear old error messages.
        remove_error($(this));
        
        let email = $(this).val();

        // Add error if email is empty and required.
        if(email == '' && $(this).attr('aria-required') == 'true'){
            add_error(errorMessages['not-email'], $(this));
        }

        // Add error if email doesn't contain an @ character.
        if(!email.includes('@')){
            add_error(errorMessages['not-email'], $(this));
        }

        // Add error if email is a disposable email.
        // let domain = email.split('@')[1].toLowerCase()
        // if ( blacklist.includes(domain)) {
        //     add_error(errorMessages['disposable'], $(this));
        // }
    };
    
    const handle_name_input_blur = function(){
        
        // Clear old error messages.
        remove_error($(this));

        var value = $(this).val();
        var label = $("label[for='" + $(this).attr('id') + "']");
        var first = '';
        var last = '';
        var first_bool = label.text().toLowerCase().includes('first');
        var name_bool = label.text().toLowerCase().includes('your name');
        var last_bool = label.text().toLowerCase().includes('last');

        if (
            !first_bool &&
            !name_bool &&
            !last_bool
        ) {
            return;
        }

        if( first_bool || name_bool ) {
            first = value.toLowerCase();
            nameObj.first_name = first;
            nameObj.first_name_element = $(this);
        }

        if( last_bool ){
            last = value.toLowerCase();
            nameObj.first_name = last;
        }

        if ( first_bool || name_bool ){

            let chars = first.trim();

            // If the first name is only 1 character long, add an error.
            if (chars.length == 1){
                add_error( errorMessages['one-char'], $(this))
            }

            // If the first name is only 2 characters long, check if they are the same or both vowels.
            if (chars.length == 2 && (chars[0].toString() == chars[1].toString() || ( vowels.includes(chars[0]) && vowels.includes(chars[1]) ) )){
                add_error( errorMessages['two-char'], $(this))
            }

            // If the first name contains a number, add an error.
            if( containsNumber(first) ){
                add_error( errorMessages['name-number'], $(this))
            }

            // If the first name is 'first', add an error.
            if( first == 'first' ){ 
                add_error( errorMessages['first-last'], $(this))
            }
        }

        if( last_bool ){

            // If the last name contains a number, add an error.
            if(containsNumber(last) ) {
                add_error( errorMessages['name-number'], $(this))
            }

            // If the last name is 'last', add an error.
            if( last == 'last' ){ 
                add_error( errorMessages['first-last'], $(this))
            } 
        }

    }

    const containsNumber = function(str) {
        return /[0-9]/.test(str);
    }

    const can_submit_form = function(e) {

        // Add error if first name and last name are the same.
        if ( nameObj.first_name == nameObj.last_name && nameObj.first_name_element != null ) {
            add_error( errorMessages['first-last'], nameObj.first_name_element);
        }

        let errors = Object.values(errorArray).filter(el => {
            return el !== false && el !== null
        });

        if(errors.length > 0) {
            e.preventDefault();
            window["gf_submitting_"+$(this).data("formid")] = false;
            if($("form[data-formid='"+$(this).data("formid")+"']").parent().find('.ee-submit-notice').length <= 0) {
                $(this).closest('form').after('<div class="ee-submit-notice ee-ff-validation">Please fix the errors notated above to be able to submit the form.</div>')
            }

            return false;
        }

        $("form[data-formid='"+$(this).data("formid")+"']").parent().find('.ee-submit-notice').remove();
        return true;
    }

    // events
    $(document).ready(_init)
    $('body').on('blur', '[type="email"]', handle_email_input_blur);
    $('body').on('blur', '[type="text"]', handle_name_input_blur);
    $('form').on('submit', can_submit_form);
    // jQuery(document).on('gform_post_render', function(){
    //     $('form').on('submit', can_submit_form)
    // }); 

})(jQuery);