/** This code is Property of Frost Sapphire Studios, all rights reserved.
*	All modification is stricty forbidden without Frost Sapphire Studios Agreement
**/

function ReplaceNotNumeric(obj) {
    var str = document.getElementById(obj).value;
    var reg = new RegExp("[^0-9]", "gi");
    var newstr = "";
    var found = false;
    if(str == "0")
        return;
        
    for(var i=0;i<str.length;i++)
        if(!str[i].match(reg))
        {
            if(!found && str[i] == "0")
                continue;
            
            if(!found && str[i] != "0")
                found = true;
            newstr += str[i];
        }
    if(newstr.length < 1)
        newstr = "0";
    document.getElementById(obj).value = newstr;
}
