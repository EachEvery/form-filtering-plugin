(function ($) {

    if($('form').length <= 0) return;
    if($('[type="email"]').length <= 0) return;

    let formType = $('form').find('.gform-body').length > 0 ? 'gforms' : null;
    let $emailFields = $('[type="email"]')
    let $textFields = $('[type="text"]')
    let errorMessages;
    let first = '';
    let last = '';
    let vowels = [
        "a",
        "e",
        "i",
        "o",
        "u"
    ]

    let blacklist = ["test.com", "10minutemail.com", "10minutemail.net" ];
    let errorArray = []

    const _init = async function() {
        response = await fetch(formFilter.list)
        blacklist = await response.json();
        errorMessages = JSON.parse(formFilter.errors)
    }

    const add_error = function(error, el) {        
        if(formType == 'gforms'){
            if(el.parent().find('.ee-ff-validation').length <= 0 && errorArray[el.attr('name')] !== false){
                el.parent().addClass('ee-ff-error')
                errorArray[el.attr('name')] = error;
                el.after(`<div class="ee-ff-validation">${error}</div>`)
            }
        }
    }

    const remove_error = function(el) {
        if(formType == 'gforms'){
            if(errorArray[el.attr('name')]) {
                errorArray[el.attr('name')] = null;
            }
            el.parent().removeClass('ee-ff-error')
            el.parent().find('.ee-ff-validation').remove();
        }
    }

    const test_email = function() {
        let email = $(this).val();
        if(email == '') {
            remove_error($(this))
            return;
        }
        _checkBlacklist(email, $(this))
        remove_error($(this))
        if(error){
            add_error(error, $(this))
        }
    }
    
    const test_name_fields = function(){
        var value = $('#'+$(this).attr('id')).val();
        var label = $("label[for='" + $(this).attr('id') + "']");
        remove_error($(this))
        if(label.text().toLowerCase().includes('first') || label.text().toLowerCase().includes('your name')){
            first = value.toLowerCase();
            test_first_chars(first, $(this))
        }
        if(label.text().toLowerCase().includes('last')){
            last = value.toLowerCase();
        }
        
        if( first == 'first' || last == 'last' ){ 
            add_error( errorMessages['first-last'], $(this))
        } else if(first == last) {
            add_error( errorMessages['first-last'], $(this))
        }
    }

    const test_first_chars = function(first, el){
        let chars = first.trim();
        if(chars.indexOf(' ') >= 0) {
            remove_error($(this))
            return
        }
        if(chars.length > 2) {
            remove_error($(this))
            return;
        }
        if(chars.length == 1){
            add_error( errorMessages['one-char'], el)
        } else if(chars.length == 2){
            if(( vowels.includes(chars[0]) && vowels.includes(chars[0]) ) || chars[0] == chars[1] ){
                add_error( errorMessages['two-char'], el)
            }
        }
    }

    const _checkBlacklist = function(email, el) {
        if(email == '' && el.attr('aria-required') == 'true'){
            error = errorMessages['not-email'];
            return true;
        }
        if(!email.includes('@')){
            error = errorMessages['not-email'];
            return true;
        }
        let domain = email.split('@')[1].toLowerCase()
        if ( blacklist.includes(domain)) {
            error = errorMessages['disposable']
            return true;
        }
        error = false
        return false;
    }

    const can_submit_form = function(e) {
        let errors = Object.values(errorArray).filter(el => {
            return el !== false && el !== null
        });
        if(errors.length > 0) {
            window["gf_submitting_"+$(this).data("formid")]=false;
            return false;
        }
        return true;
    }

    //events
    $(document).ready(_init)
    $emailFields.on('blur', test_email)
    $textFields.on('blur', test_name_fields)
    jQuery(document).on('gform_post_render', function(){
        $('form').on('submit', can_submit_form)
    }); 

})(jQuery);