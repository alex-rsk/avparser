(async () =>
    {
        const childSelector = '//button/div/img';
        var elementPresent = function () {
            let option = document.evaluate(childSelector , document, null, XPathResult.ANY_TYPE, null).iterateNext();
            return !!option;
        };
        while (!elementPresent()) {
            await new Promise(r => setTimeout(r, 30));
        }
        let childElement  = document.evaluate(childSelector , document, null, XPathResult.ANY_TYPE, null).iterateNext();
        let element = childElement.parentElement.parentElement;

        let elementClass = element.getAttribute('class');
        elementClass = '.'+elementClass.split(' ')[0].replace(' ','.', elementClass);

        let elements = document.querySelectorAll(elementClass);

        let suggestions = [];
        elements.forEach((e) =>
        {
            let properties = Object.keys(Object.getOwnPropertyDescriptors(e));
            let propData = properties.filter(name => name.indexOf('reactEvents') !== -1);
            if (propData.length === 0)
            {
                propData = properties.filter(name => name.indexOf('reactProps') !== -1);
            }
            if (propData.length > 0)
            {
                if (e[propData[0]].children && e[propData[0]].children.props)
                {
                    let suggestion = e[propData[0]].children.props.suggestion;
                    if (suggestion)
                    {
                        suggestions.push({
                            page_id: suggestion.id, 
                            page_name: suggestion.name,
                            picture : suggestion.imageURI,
                            ig_alias : suggestion.igUsername,
                            fb_alias : suggestion.pageAlias,
                            priority : 1
                        });
                    }
                }
            }
        });
    return suggestions;
})();
