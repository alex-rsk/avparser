( async (query) => 
{  
  const childSelector = '//button/div/img';
  var elementPresent = function () {
     let option = document.evaluate(childSelector, document, null, XPathResult.ANY_TYPE, null).iterateNext();
    return !!option;
 };
  while (!elementPresent()) {
    await new Promise(r => setTimeout(r, 30));
 }
 
 let childElement = document.evaluate(childSelector, document, null, XPathResult.ANY_TYPE, null).iterateNext();
 let element = childElement.parentElement.parentElement;
 element.click(); 
})();
