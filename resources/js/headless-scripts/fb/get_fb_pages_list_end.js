(()=> {
    let xPath='/html/body/div[1]/div/div[1]/div[1]/div[3]/div/div/div[1]/div[1]/div[2]/div/div/div/div/div/div//div[last()-1]';
    let lastElement = document.evaluate(xPath, document, null, XPathResult.ANY_TYPE, null).iterateNext();
    if (lastElement)
    {
        if (!lastElement.getAttribute('class') && lastElement.querySelector('span[dir="auto"]') 
                && /(End Of Results|Результатов больше нет)/usi.test(lastElement.querySelector('span[dir="auto"]').innerText))
        {
            return 1;
        }
        else {
            return 0;
        }
    }
    
})();
