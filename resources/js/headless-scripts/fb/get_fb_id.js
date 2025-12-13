(() => {    
    var getFbId = () =>
    {
            let result = { "id": 0, "alt_id" : "" };
            if (!document.querySelector('form[action*="/logout"]')) {
                return result;
            }
            let cookie = /c_user=(\d+)/.exec(document.cookie);
            return {"id" : cookie[1], "alt_id" : ""};
    };

    var getFbIdOld = () =>
    {
        if (document.querySelector('*[data-click="home_icon"]'))
        {
            var idXpath = '/html/body/div[1]/div[2]/div/div[1]/div/div/div/div[2]/div[1]/div[1]/div/a';
            var xpr = document.evaluate(idXpath, document, null, XPathResult.ANY_TYPE, null);
            var id = '0'; //ID аккаунта номерной
            var altId = ''; //ID аккаунта буквенный            
            if (!xpr.invalidIteratorState)
            {
                var idElement = xpr.iterateNext();
                if (!idElement)
                {
                    idElement = document.querySelector('a[data-gt="{"chrome_nav_item":"timeline_chrome"}"]');
                }                
                var numericId = /\?id=(.+)$/.test(idElement.getAttribute('href'));
                if (numericId)
                {                    
                    id = /\?id=(.+)$/.exec(idElement.getAttribute('href'))[1];
                    altId = id;
                }
                else 
                {
                    altId= /facebook\.com\/(.*)$/.exec(idElement.getAttribute('href'))[1];
                    var idNumXpath = 'span/img';
                    xpr = document.evaluate(idNumXpath, idElement, null, XPathResult.ANY_TYPE, null);
                    var idNumElement = xpr.iterateNext(),
                        extractIdRegexp =/profile_pic_header_(\d+)/;
                    if (idNumElement && extractIdRegexp.test(idNumElement.getAttribute('id')))
                    {
                        id = extractIdRegexp.exec(idNumElement.getAttribute('id'))[1];
                    }
                }
                
            }
            return { "id":id, "alt_id" : altId };
        } else {
            return 0;
        }
    };

   fbId = getFbIdOld();
   if (fbId == 0 || (typeof(fbId.id) != "undefined" && fbId.id == 0)) {
       var fbId = getFbId();
   }
   return fbId;
})();
