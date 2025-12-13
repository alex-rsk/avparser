((selector, xpath)=>{
    if (!xpath) {
        return document.querySelector(selector) !== null ? 1 : 0;
    }
    else
    {
        var xPathResult = document.evaluate(selector, document);
        var element = xPathResult.iterateNext();
        return element !== null ? 1 : 0;
    }
})('{{selector}}',{{xpath}})