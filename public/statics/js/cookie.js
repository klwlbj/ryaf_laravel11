function setCookie(key,value,exDays)
{
    var d = new Date();
    d.setTime(d.getTime()+(exDays*24*60*60*1000));
    var expires = "expires="+d.toGMTString();
    document.cookie = key + "=" + value + "; " + expires + "; path=/";
}

function getCookie(key)
{
    var name = key + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++)
    {
        var c = ca[i].trim();
        if (c.indexOf(name)==0) return c.substring(name.length,c.length);
    }
    return "";
}
