!function($){var i={init:function t(){$(function(){$("form#post").each(function(){var t={$:$(this),_:this};t.$.data("APKForms",t),i.configure(t)})})},configure:function r(t){t.$haction=$("input#hiddenaction",t.$),t.haction=t.$haction.val(),t.report={tooltips:!0,inline:!1},t.$title=$("input#title",t.$),t.typeSave="publish",t.$.bind("submit.APKForms",function(r){r.preventDefault(),i.validate(t)}),t.$publish=$("input#publish",t.$),t.$saveDraft=$("input#save-post",t.$),t.$saveDraft.bind("click",function(){t.typeSave="draft"})},validate:function e(i){return PikiForms.clearErrors(i),""===i.$title.val()?(PikiForms.showInlineMessage(i,{message:"O campo título é obrigatório"},"error"),void(i.typeSave="publish")):"draft"===i.typeSave?(i.$.unbind("submit.APKForms"),i.$.submit(),!0):(i.$haction.val("admin_form_validate"),$.fn.pikiLoader(),void i.$.ajaxSubmit({url:ajaxurl,type:"POST",dataType:"text",iframe:!1,success:function(t,r,e,o){i.$haction.val(i.haction);try{var a=$.parseJSON(t)}catch(n){return $.fn.pikiAlert(t+"<br /><br />"+r+"<br /><br />"),!1}"success"===a.status?(i.$.unbind("submit.APKForms"),i.$publish.click()):($.fn.pikiLoader("close"),"valida"===a.error_type&&PikiForms.setErrors(i,a.errors))},error:function(i,t,r,e){$.fn.pikiLoader("close"),console.log("Erro!!!"),console.log(i)}}))},validateAfter:function o(i,t){i.$haction.val(i.haction);try{var r=$.parseJSON(responseText)}catch(e){return $.fn.pikiAlert(responseText+"<br /><br />"+statusText+"<br /><br />"),!1}"success"===r.status?i.$.unbind("submit.APKForms").submit():($.fn.pikiLoader("close"),"valida"===r.error_type&&PikiForms.setErrors(i,r.errors))}};i.init()}(jQuery);