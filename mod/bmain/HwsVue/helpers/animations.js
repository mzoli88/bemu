global.fadeOut = function fade(el, time) {
    if (!el) return;
    if (empty(el.style.opacity)) el.style.opacity = 1;
    el.style.opacity < 0
        ? (el.style.display = "none")
        : setTimeout(() => {
            el.style.opacity = parseFloat(el.style.opacity) - 0.1;
            fade(el, time);
        }, time || 30);
};

global.fadeIn = function fade(el, time) {
    if (!el) return;
    if (empty(el.style.opacity)) el.style.opacity = 0;
    if (el.style.display == "none") el.style.display = null;
    if (el.style.opacity < 1)
        setTimeout(() => {
            el.style.opacity = parseFloat(el.style.opacity) + 0.1;
            fade(el, time);
        }, time || 30);
};

global.moveX = function move(el, to, time, callBack, from) {
    if (!el) {
        if (callBack) callBack();
        return;
    };
    to = empty(to) ? 200 : to;
    let pos = 0;

    if (from) {
        pos = from;
        el.style.transform = 'translateX(' + pos + 'px)';
    } else {
        if (el.style.transform) {
            pos = parseInt(el.style.transform.replace('translateX(', '').replace('px)', ''));
        }
    }


    let way = pos < to;

    if (to == pos) {
        if (callBack) callBack();
        return;
    }

    let timer = setInterval(function () {
        if (way) pos += 5; else pos -= 5;
        if (pos == 0) {
            el.style.transform = null;
        } else {
            el.style.transform = 'translateX(' + pos + 'px)';
        }
        // dd(to, pos);
        if ((way == true && pos >= to) || (way == false && pos <= to)) {
            clearInterval(timer);
            if (callBack) callBack();
            return;
        }
    }, time || 10);
}