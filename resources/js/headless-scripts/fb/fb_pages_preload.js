 let oldXHROpen = window.XMLHttpRequest.prototype.open;
 pages = [];
 error = false;
 requests = 0;
 window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) { 
 this.addEventListener('load', function() {
    let regexp = /Ya\[\d+\]\('(.*)'\)/;
    if (url.contains('bulk-route-definitions/'))
    {
        let payload = this.responseText.replace('for (;;);', '');
        try {
            let payloadObj = JSON.parse(payload);
            for (let pageKey in payloadObj.payload.payloads)
            {
                let element = payloadObj.payload.payloads[pageKey];
                if (element && !element.error && element.result 
                        && element.result.exports && element.result.exports.rootView)
                {
                     let pageId = element.result.exports.rootView.props.pageID;
                     if (-1 == pages.findIndex(obj => obj.page_id == pageId))
                     {
                        let pageInfo = {
                            'page_name' : element.result.exports.meta.title,
                            'page_id' : pageId,
                            'picture' :'',
                        };
                        pages.push(pageInfo);
                    }
                }
            }
            
        }
        catch (e)
        {

        }
    }
    if (url.contains('graphql'))
    {
        requests++;
        try {
            let payloadObj = JSON.parse(this.responseText);
            if (payloadObj.errors.length>0 && payloadObj.errors[0].code=='1675004')  
            {
                error = '{"error": "Limit exceeded", "requests_passed": "'+requests+'"}';
            }
        }
        catch (e)
        {

        }
    }
 });

 return oldXHROpen.apply(this, arguments);
}