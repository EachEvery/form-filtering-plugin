(function ($) {

    if($('form').length <= 0) return;
    if($('[type="email"]').length <= 0) return;

    let formType = $('form').find('.gform-body').length > 0 ? 'gforms' : null;
    let errorMessages;
    let first = '';
    let last = '';
    let nameArray = [];
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
        if(!label.text().toLowerCase().includes('first') && !label.text().toLowerCase().includes('your name') && !label.text().toLowerCase().includes('last') ) return;

        if(label.text().toLowerCase().includes('first') || label.text().toLowerCase().includes('your name')){
            first = value.toLowerCase();
        }
        if(label.text().toLowerCase().includes('last')){
            last = value.toLowerCase();
        }

        if(first == last) {
            add_error( errorMessages['first-last'], $(this))
        }  else {
            let text = Object.entries(errorArray).filter( function([key, value]){
                return value == errorMessages['first-last'];
            });
            if(text.length != 0){
                let _this = $(this)
                nameArray.forEach(function(el){
                    let id = _this.attr('id').split('_');
                    console.log(id)
                    let starts = id[0]+'_'+id[1]
                    console.log(starts)
                    console.log(el)
                    console.log(nameArray)
                    if(el.startsWith(starts)){
                        let $set = $(`#${el}`);
                        remove_error($set)
                    }
                })
            }
        }

        if(label.text().toLowerCase().includes('first') || label.text().toLowerCase().includes('your name')){
            nameArray.indexOf($(this).attr('id')) === -1 ? nameArray.push($(this).attr('id')) : '';
            test_first_chars(first, $(this))
            if( containsNumber(first) ){
                add_error( errorMessages['name-number'], $(this))
            }
            if( first == 'first' ){ 
                add_error( errorMessages['first-last'], $(this))
            }
        }
        if(label.text().toLowerCase().includes('last')){
            nameArray.indexOf($(this).attr('id')) === -1 ? nameArray.push($(this).attr('id')) : '';
            if(containsNumber(last) ) {
                add_error( errorMessages['name-number'], $(this))
            }
            if( last == 'last' ){ 
                add_error( errorMessages['first-last'], $(this))
            } 
        }
        
        
       
        
       
    }

    const containsNumber = function(str) {
        return /[0-9]/.test(str);
      }
    const test_first_chars = function(first, el){
        let chars = first.trim();
        if(chars.indexOf(' ') >= 0) {
            return
        }
        if(chars.length > 2) {
            return;
        }
        if(chars.length == 1){
            add_error( errorMessages['one-char'], el)
        } else if(chars.length == 2){
            if( chars[0].toString() == chars[1].toString() || ( vowels.includes(chars[0]) && vowels.includes(chars[1]) ) ){ 
                remove_error(el)
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
            if($("form[data-formid='"+$(this).data("formid")+"']").parent().find('.ee-submit-notice').length <= 0) {
                $(this).closest('form').after('<div class="ee-submit-notice ee-ff-validation">Please fix the errors notated above to be able to submit the form.</div>')
            }
            return false;
        }
        $("form[data-formid='"+$(this).data("formid")+"']").parent().find('.ee-submit-notice').remove();
        return true;
    }

    //events
    $(document).ready(_init)
    $('body').on('blur', '[type="email"]', test_email)
    $('body').on('blur', '[type="text"]', test_name_fields)
    jQuery(document).on('gform_post_render', function(){
        $('form').on('submit', can_submit_form)
    }); 

})(jQuery);