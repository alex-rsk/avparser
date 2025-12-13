let oldXHROpen = window.XMLHttpRequest.prototype.open;
 myads = [];
 window.XMLHttpRequest.prototype.open = function(method, url, async, user, password) { 
 this.addEventListener('load', function() {
    if (url.indexOf('grab')!==-1)
    {
        try {
            let payload = JSON.parse(this.responseText);
            myads.push(payload.direct);
        }
        catch (e)
        {

        }
    }
 });

 return oldXHROpen.apply(this, arguments);
}