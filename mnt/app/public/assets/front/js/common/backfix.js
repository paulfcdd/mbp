let count = 0;
window.onload = function () {
    if (typeof history.pushState === "function") {
        history.pushState("back", null, null);
        window.onpopstate = function () {
            history.pushState('back', null, null);
            if(count === 1) window.location.href = window.location.origin
        };
    }
}
setTimeout(function(){count = 1;},200);