(()=> {
    var selector ='//*[@id="content"]/div/div[2]/div[2]/div[1]/div[2]/div/div[1]/div[1]/div/div[2]/div/div[1]/div/span';
    var switchElement = document.evaluate(selector, document, null, XPathResult.ANY_TYPE, null).iterateNext();
    if (switchElement)
    {
        switchElement.click();
    }
})();