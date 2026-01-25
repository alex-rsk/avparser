((elementSelector, xPath)=>{
    let element = null;
    if (!xPath)
    {
        let elements = document.querySelectorAll(elementSelector);
        element = elements[elements.length-1];
    }
    else {
        element = document.evaluate(elementSelector, document, null,XPathResult.ANY_TYPE, null).iterateNext()
    }

    if (element)
    {
        console.log(element.getBoundingClientRect());
        return element.style.backgroundImage;
    }
    else {
        return false;
    }
})('{{elementSelector}}',{{Xpath}});