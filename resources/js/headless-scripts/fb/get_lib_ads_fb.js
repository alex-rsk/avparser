(() =>
{        
    var adObjects = [];    
    document.querySelectorAll('._7owt').forEach((e) => {
        var adObj = [];
        var properties = Object.keys(Object.getOwnPropertyDescriptors(e));
        var propData = properties.filter(name => name.indexOf('reactProps') !== -1);
        if (propData.length > 0)
        {            
            var el = e[propData[0]].children.props.adCard;
            if (el && el.publisherPlatform.indexOf('facebook') !== -1)
            {
                var textElement = e.querySelector('div[tabindex="0"][role="button"]>div._4ik4._4ik5');
                var text = '';
                if (textElement)
                {
                    text = textElement.innerText;
                }
                else {
                    text = el.caption;
                }
                var adDatesSelector = 'div/div[1]/div[1]/div[1]/div[2]';
                var adDatesElement = document.evaluate(adDatesSelector, e, null, 
                    XPathResult.ANY_TYPE, null).iterateNext();
                var adDatesProperties = Object.keys(Object.getOwnPropertyDescriptors(adDatesElement));
                var adDatesPropData = adDatesProperties.filter(name => name.indexOf('reactEvents') !== -1);
                var endDate  = null;
                if (adDatesPropData.length>0)
                {
                    endDate = adDatesElement[adDatesPropData[0]].children.props.endDate;
                }
                var shot = el.snapshot;
                var cards = shot.cards;
                
                if (cards.length > 0)
                {
                    switch (shot.displayFormat)
                    {
                        case 'multi-images':
                        case 'carousel':
                          
                            var adObj = {
                                    id: el.adArchiveID,
                                    displayFormat: shot.displayFormat,
                                    pageId: el.pageID,
                                    creativeId: shot.adCreativeId,
                                    startDate: el.startDate,
                                    endDate: el.endDate,
                                    fbUrl : shot.pageProfileUri,
                                    pageName :  el.pageName,
                                    isActive: el.isActive,
                                    title: shot.title,
                                    fbPic : shot.pageProfilePictureUrl,
                                    caption: text,
                                    adCreativeId: shot.adCreativeId,
                                    cta: shot.ctaType || 'NONE',
                                    link: shot.extraLinks,  
                                    link_text : [],
                                    image: [],
                                    video : []
                                };
                            cards.forEach((card) => {
                               adObj.image.push(!card.video_sd_url? 
                                        card.original_image_url:card.video_preview_image_url);
                               adObj.video.push(card.video_sd_url);
                               adObj.link.push(card.link_url);
                               adObj.link_text.push(card.title);
                            });
                            adObjects.push(adObj);
                            break;
                    
                        case 'dpa':
                        {
                           
                            cards.forEach((card, cardIndex) => {
                                var adObj = {
                                    id: el.adArchiveID,
                                    displayFormat: shot.displayFormat,
                                    pageId: el.pageID,
                                    creativeId: shot.adCreativeId+'_'+cardIndex,
                                    startDate: el.startDate,
                                    endDate: el.endDate,
                                    fbUrl : shot.pageProfileUri,
                                    pageName :  el.pageName,
                                    isActive: el.isActive,
                                    title: card.body,
                                    fbPic : shot.pageProfilePictureUrl,
                                    caption: text+'.'+card.title,
                                    adCreativeId: shot.adCreativeId,
                                    cta: shot.ctaType || 'NONE',
                                    link: card.link_url,
                                    link_text: card.link_description,
                                    image: !card.video_sd_url? 
                                        card.original_image_url:card.video_preview_image_url,
                                    video: card.video_sd_url
                                };
                                adObjects.push(adObj);
                            });
                            break;
                        }
                        case 'dco':
                             var adVariants = [];
                             cards.forEach((card, cardIndex) => {
                                var captionHash = card.body+card.link_description;
                                if (typeof(adVariants[captionHash]) === "undefined")
                                {
                                    adVariants[captionHash]= {
                                        id: el.adArchiveID,
                                        displayFormat: shot.displayFormat,
                                        pageId: el.pageID,
                                        creativeId: shot.adCreativeId+'_'+cardIndex,
                                        startDate: el.startDate,
                                        endDate: el.endDate,
                                        fbUrl : shot.pageProfileUri,
                                        pageName :  el.pageName,
                                        isActive: el.isActive,
                                        title: card.body,
                                        fbPic : shot.pageProfilePictureUrl,
                                        caption: text+'.'+card.title,
                                        adCreativeId: shot.adCreativeId,
                                        cta: shot.ctaType || 'NONE',
                                        link: card.link_url,
                                        link_text: card.link_description,
                                        image: [!card.video_sd_url? 
                                            card.original_image_url:card.video_preview_image_url],
                                        video: card.video_sd_url ? [card.video_sd_url] : []
                                    };
                                }
                                else {
                                    if (!card.video_sd_url)
                                    {
                                        adVariants[captionHash].image.push(card.original_image_url);
                                    }
                                    else {
                                        adVariants[captionHash].image.push(card.video_preview_image_url);
                                        adVariants[captionHash].video.push(card.video_sd_url);
                                    }
                                }
                            });
                            for(let index in adVariants)
                            {
                                adObjects.push(adVariants[index]);
                            }
                        break;
                    }
                } else {
                    //displayFormat = 'image' или 'video'

                    var videos = [];
                    var images = [];

                    shot.videos.forEach((vid)=>{
                       videos.push(vid.video_hd_url || vid.video_sd_url);
                       images.push(vid.video_preview_image_url);
                    });

                    shot.images.forEach((img)=>{
                       images.push(img.original_image_url);
                    });

                    
                    var adObj = {
                        id: el.adArchiveID,
                        displayFormat: shot.displayFormat,
                        pageId: el.pageID,
                        creativeId: shot.adCreativeId,
                        startDate: el.startDate,
                        endDate: el.endDate,
                        fbUrl : shot.pageProfileUri,
                        pageName :  el.pageName,
                        isActive: el.isActive,
                        title: shot.currentPageName,
                        fbPic : shot.pageProfilePictureUrl,
                        caption: shot.body.markup.__html,
                        adCreativeId: shot.adCreativeId,
                        cta: shot.ctaType || 'NONE',
                        link: shot.linkUrl,
                        link_text : shot.linkDescription,
                        image: images.length === 0 ? null : images,
                        video: videos.length === 0 ? null : videos,
                    };
                    adObjects.push(adObj);
                }
            }
        }
    });
    return adObjects;
})();
