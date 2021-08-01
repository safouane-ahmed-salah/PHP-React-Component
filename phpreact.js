! function(t, e) {
    var url = e.currentScript.src.replace(/(\.min)?\.js/,'.php');
    var n = function() {
            e.querySelectorAll("[component] *,[component]").forEach(function(t) {
                t.setState || (t.getState = c, t.setState = o)
            })
        },
        o = function(t, o) {
            var c = this.hasAttribute("component") ? this : this.closest("[component]");
            if (c) {
                var i = [],
                    r = this.getAttribute("key"),
                    s = (document.activeElement, this.value),
                    a = this.getState();
                c.querySelectorAll("[component]").forEach(function(t) {
                    i.push(t.getAttribute("component"))
                }), "function" == typeof t && (t = t(a));
                var u = new XMLHttpRequest,
                    p = {
                        current: c.getAttribute("component"),
                        components: i,
                        state: t
                    };
                u.onreadystatechange = function() {
                    if (4 == this.readyState && 200 == this.status && this.responseText) {
                        var t, i = e.createElement("div");
                        i.innerHTML = this.responseText, r && (t = i.querySelector("[key=\'" + r + "\']")), c.replaceWith(i.childNodes[0]), t && (t.focus(), s && (t.value = "", t.value = s)), "function" == typeof o && o(), n()
                    }
                }, u.open("POST", url, !0), u.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), u.send("phpreact=" + JSON.stringify(p))
            }
        },
        c = function() {
            try {
                var t = this.closest("[component]");
                return JSON.parse(t.getAttribute("component-state"))
            } catch (t) {
                return {}
            }
        };
    t.addEventListener("load", n)
}(window, document);