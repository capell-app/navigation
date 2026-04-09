function ce(e) {
    return (
        e !== null &&
        typeof e == 'object' &&
        'constructor' in e &&
        e.constructor === Object
    )
}
function de(e = {}, s = {}) {
    const t = ['__proto__', 'constructor', 'prototype']
    Object.keys(s)
        .filter((i) => t.indexOf(i) < 0)
        .forEach((i) => {
            typeof e[i] > 'u'
                ? (e[i] = s[i])
                : ce(s[i]) &&
                  ce(e[i]) &&
                  Object.keys(s[i]).length > 0 &&
                  de(e[i], s[i])
        })
}
const ve = {
    body: {},
    addEventListener() {},
    removeEventListener() {},
    activeElement: { blur() {}, nodeName: '' },
    querySelector() {
        return null
    },
    querySelectorAll() {
        return []
    },
    getElementById() {
        return null
    },
    createEvent() {
        return { initEvent() {} }
    },
    createElement() {
        return {
            children: [],
            childNodes: [],
            style: {},
            setAttribute() {},
            getElementsByTagName() {
                return []
            },
        }
    },
    createElementNS() {
        return {}
    },
    importNode() {
        return null
    },
    location: {
        hash: '',
        host: '',
        hostname: '',
        href: '',
        origin: '',
        pathname: '',
        protocol: '',
        search: '',
    },
}
function N() {
    const e = typeof document < 'u' ? document : {}
    return (de(e, ve), e)
}
const Oe = {
    document: ve,
    navigator: { userAgent: '' },
    location: {
        hash: '',
        host: '',
        hostname: '',
        href: '',
        origin: '',
        pathname: '',
        protocol: '',
        search: '',
    },
    history: { replaceState() {}, pushState() {}, go() {}, back() {} },
    CustomEvent: function () {
        return this
    },
    addEventListener() {},
    removeEventListener() {},
    getComputedStyle() {
        return {
            getPropertyValue() {
                return ''
            },
        }
    },
    Image() {},
    Date() {},
    screen: {},
    setTimeout() {},
    clearTimeout() {},
    matchMedia() {
        return {}
    },
    requestAnimationFrame(e) {
        return typeof setTimeout > 'u' ? (e(), null) : setTimeout(e, 0)
    },
    cancelAnimationFrame(e) {
        typeof setTimeout > 'u' || clearTimeout(e)
    },
}
function V() {
    const e = typeof window < 'u' ? window : {}
    return (de(e, Oe), e)
}
function ke(e = '') {
    return e
        .trim()
        .split(' ')
        .filter((s) => !!s.trim())
}
function Ae(e) {
    const s = e
    Object.keys(s).forEach((t) => {
        try {
            s[t] = null
        } catch {}
        try {
            delete s[t]
        } catch {}
    })
}
function ye(e, s = 0) {
    return setTimeout(e, s)
}
function U() {
    return Date.now()
}
function ze(e) {
    const s = V()
    let t
    return (
        s.getComputedStyle && (t = s.getComputedStyle(e, null)),
        !t && e.currentStyle && (t = e.currentStyle),
        t || (t = e.style),
        t
    )
}
function De(e, s = 'x') {
    const t = V()
    let i, a, n
    const l = ze(e)
    return (
        t.WebKitCSSMatrix
            ? ((a = l.transform || l.webkitTransform),
              a.split(',').length > 6 &&
                  (a = a
                      .split(', ')
                      .map((o) => o.replace(',', '.'))
                      .join(', ')),
              (n = new t.WebKitCSSMatrix(a === 'none' ? '' : a)))
            : ((n =
                  l.MozTransform ||
                  l.OTransform ||
                  l.MsTransform ||
                  l.msTransform ||
                  l.transform ||
                  l
                      .getPropertyValue('transform')
                      .replace('translate(', 'matrix(1, 0, 0, 1,')),
              (i = n.toString().split(','))),
        s === 'x' &&
            (t.WebKitCSSMatrix
                ? (a = n.m41)
                : i.length === 16
                  ? (a = parseFloat(i[12]))
                  : (a = parseFloat(i[4]))),
        s === 'y' &&
            (t.WebKitCSSMatrix
                ? (a = n.m42)
                : i.length === 16
                  ? (a = parseFloat(i[13]))
                  : (a = parseFloat(i[5]))),
        a || 0
    )
}
function Y(e) {
    return (
        typeof e == 'object' &&
        e !== null &&
        e.constructor &&
        Object.prototype.toString.call(e).slice(8, -1) === 'Object'
    )
}
function Ge(e) {
    return typeof window < 'u' && typeof window.HTMLElement < 'u'
        ? e instanceof HTMLElement
        : e && (e.nodeType === 1 || e.nodeType === 11)
}
function $(...e) {
    const s = Object(e[0])
    for (let t = 1; t < e.length; t += 1) {
        const i = e[t]
        if (i != null && !Ge(i)) {
            const a = Object.keys(Object(i)).filter(
                (n) =>
                    n !== '__proto__' &&
                    n !== 'constructor' &&
                    n !== 'prototype',
            )
            for (let n = 0, l = a.length; n < l; n += 1) {
                const o = a[n],
                    r = Object.getOwnPropertyDescriptor(i, o)
                r !== void 0 &&
                    r.enumerable &&
                    (Y(s[o]) && Y(i[o])
                        ? i[o].__swiper__
                            ? (s[o] = i[o])
                            : $(s[o], i[o])
                        : !Y(s[o]) && Y(i[o])
                          ? ((s[o] = {}),
                            i[o].__swiper__ ? (s[o] = i[o]) : $(s[o], i[o]))
                          : (s[o] = i[o]))
            }
        }
    }
    return s
}
function W(e, s, t) {
    e.style.setProperty(s, t)
}
function Se({ swiper: e, targetPosition: s, side: t }) {
    const i = V(),
        a = -e.translate
    let n = null,
        l
    const o = e.params.speed
    ;((e.wrapperEl.style.scrollSnapType = 'none'),
        i.cancelAnimationFrame(e.cssModeFrameID))
    const r = s > a ? 'next' : 'prev',
        f = (u, v) => (r === 'next' && u >= v) || (r === 'prev' && u <= v),
        g = () => {
            ;((l = new Date().getTime()), n === null && (n = l))
            const u = Math.max(Math.min((l - n) / o, 1), 0),
                v = 0.5 - Math.cos(u * Math.PI) / 2
            let d = a + v * (s - a)
            if (
                (f(d, s) && (d = s), e.wrapperEl.scrollTo({ [t]: d }), f(d, s))
            ) {
                ;((e.wrapperEl.style.overflow = 'hidden'),
                    (e.wrapperEl.style.scrollSnapType = ''),
                    setTimeout(() => {
                        ;((e.wrapperEl.style.overflow = ''),
                            e.wrapperEl.scrollTo({ [t]: d }))
                    }),
                    i.cancelAnimationFrame(e.cssModeFrameID))
                return
            }
            e.cssModeFrameID = i.requestAnimationFrame(g)
        }
    g()
}
function be(e) {
    return (
        e.querySelector('.swiper-slide-transform') ||
        (e.shadowRoot &&
            e.shadowRoot.querySelector('.swiper-slide-transform')) ||
        e
    )
}
function H(e, s = '') {
    const t = V(),
        i = [...e.children]
    return (
        t.HTMLSlotElement &&
            e instanceof HTMLSlotElement &&
            i.push(...e.assignedElements()),
        s ? i.filter((a) => a.matches(s)) : i
    )
}
function Be(e, s) {
    const t = [s]
    for (; t.length > 0; ) {
        const i = t.shift()
        if (e === i) return !0
        t.push(
            ...i.children,
            ...(i.shadowRoot ? i.shadowRoot.children : []),
            ...(i.assignedElements ? i.assignedElements() : []),
        )
    }
}
function Ve(e, s) {
    const t = V()
    let i = s.contains(e)
    return (
        !i &&
            t.HTMLSlotElement &&
            s instanceof HTMLSlotElement &&
            ((i = [...s.assignedElements()].includes(e)), i || (i = Be(e, s))),
        i
    )
}
function K(e) {
    try {
        console.warn(e)
        return
    } catch {}
}
function J(e, s = []) {
    const t = document.createElement(e)
    return (t.classList.add(...(Array.isArray(s) ? s : ke(s))), t)
}
function $e(e, s) {
    const t = []
    for (; e.previousElementSibling; ) {
        const i = e.previousElementSibling
        ;(s ? i.matches(s) && t.push(i) : t.push(i), (e = i))
    }
    return t
}
function Fe(e, s) {
    const t = []
    for (; e.nextElementSibling; ) {
        const i = e.nextElementSibling
        ;(s ? i.matches(s) && t.push(i) : t.push(i), (e = i))
    }
    return t
}
function q(e, s) {
    return V().getComputedStyle(e, null).getPropertyValue(s)
}
function Q(e) {
    let s = e,
        t
    if (s) {
        for (t = 0; (s = s.previousSibling) !== null; )
            s.nodeType === 1 && (t += 1)
        return t
    }
}
function Te(e, s) {
    const t = []
    let i = e.parentElement
    for (; i; )
        (s ? i.matches(s) && t.push(i) : t.push(i), (i = i.parentElement))
    return t
}
function _e(e, s) {
    function t(i) {
        i.target === e &&
            (s.call(e, i), e.removeEventListener('transitionend', t))
    }
    s && e.addEventListener('transitionend', t)
}
function re(e, s, t) {
    const i = V()
    return (
        e[s === 'width' ? 'offsetWidth' : 'offsetHeight'] +
        parseFloat(
            i
                .getComputedStyle(e, null)
                .getPropertyValue(
                    s === 'width' ? 'margin-right' : 'margin-top',
                ),
        ) +
        parseFloat(
            i
                .getComputedStyle(e, null)
                .getPropertyValue(
                    s === 'width' ? 'margin-left' : 'margin-bottom',
                ),
        )
    )
}
function B(e) {
    return (Array.isArray(e) ? e : [e]).filter((s) => !!s)
}
function le(e, s = '') {
    typeof trustedTypes < 'u'
        ? (e.innerHTML = trustedTypes
              .createPolicy('html', { createHTML: (t) => t })
              .createHTML(s))
        : (e.innerHTML = s)
}
let Z
function He() {
    const e = V(),
        s = N()
    return {
        smoothScroll:
            s.documentElement &&
            s.documentElement.style &&
            'scrollBehavior' in s.documentElement.style,
        touch: !!(
            'ontouchstart' in e ||
            (e.DocumentTouch && s instanceof e.DocumentTouch)
        ),
    }
}
function xe() {
    return (Z || (Z = He()), Z)
}
let ee
function Ne({ userAgent: e } = {}) {
    const s = xe(),
        t = V(),
        i = t.navigator.platform,
        a = e || t.navigator.userAgent,
        n = { ios: !1, android: !1 },
        l = t.screen.width,
        o = t.screen.height,
        r = a.match(/(Android);?[\s\/]+([\d.]+)?/)
    let f = a.match(/(iPad)(?!\1).*OS\s([\d_]+)/)
    const g = a.match(/(iPod)(.*OS\s([\d_]+))?/),
        u = !f && a.match(/(iPhone\sOS|iOS)\s([\d_]+)/),
        v = i === 'Win32'
    let d = i === 'MacIntel'
    const h = [
        '1024x1366',
        '1366x1024',
        '834x1194',
        '1194x834',
        '834x1112',
        '1112x834',
        '768x1024',
        '1024x768',
        '820x1180',
        '1180x820',
        '810x1080',
        '1080x810',
    ]
    return (
        !f &&
            d &&
            s.touch &&
            h.indexOf(`${l}x${o}`) >= 0 &&
            ((f = a.match(/(Version)\/([\d.]+)/)),
            f || (f = [0, 1, '13_0_0']),
            (d = !1)),
        r && !v && ((n.os = 'android'), (n.android = !0)),
        (f || u || g) && ((n.os = 'ios'), (n.ios = !0)),
        n
    )
}
function Ee(e = {}) {
    return (ee || (ee = Ne(e)), ee)
}
let te
function Re() {
    const e = V(),
        s = Ee()
    let t = !1
    function i() {
        const o = e.navigator.userAgent.toLowerCase()
        return (
            o.indexOf('safari') >= 0 &&
            o.indexOf('chrome') < 0 &&
            o.indexOf('android') < 0
        )
    }
    if (i()) {
        const o = String(e.navigator.userAgent)
        if (o.includes('Version/')) {
            const [r, f] = o
                .split('Version/')[1]
                .split(' ')[0]
                .split('.')
                .map((g) => Number(g))
            t = r < 16 || (r === 16 && f < 2)
        }
    }
    const a = /(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(
            e.navigator.userAgent,
        ),
        n = i(),
        l = n || (a && s.ios)
    return {
        isSafari: t || n,
        needPerspectiveFix: t,
        need3dFix: l,
        isWebView: a,
    }
}
function we() {
    return (te || (te = Re()), te)
}
function qe({ swiper: e, on: s, emit: t }) {
    const i = V()
    let a = null,
        n = null
    const l = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                (t('beforeResize'), t('resize'))
        },
        o = () => {
            !e ||
                e.destroyed ||
                !e.initialized ||
                ((a = new ResizeObserver((g) => {
                    n = i.requestAnimationFrame(() => {
                        const { width: u, height: v } = e
                        let d = u,
                            h = v
                        ;(g.forEach(
                            ({
                                contentBoxSize: y,
                                contentRect: x,
                                target: c,
                            }) => {
                                ;(c && c !== e.el) ||
                                    ((d = x ? x.width : (y[0] || y).inlineSize),
                                    (h = x ? x.height : (y[0] || y).blockSize))
                            },
                        ),
                            (d !== u || h !== v) && l())
                    })
                })),
                a.observe(e.el))
        },
        r = () => {
            ;(n && i.cancelAnimationFrame(n),
                a && a.unobserve && e.el && (a.unobserve(e.el), (a = null)))
        },
        f = () => {
            !e || e.destroyed || !e.initialized || t('orientationchange')
        }
    ;(s('init', () => {
        if (e.params.resizeObserver && typeof i.ResizeObserver < 'u') {
            o()
            return
        }
        ;(i.addEventListener('resize', l),
            i.addEventListener('orientationchange', f))
    }),
        s('destroy', () => {
            ;(r(),
                i.removeEventListener('resize', l),
                i.removeEventListener('orientationchange', f))
        }))
}
function We({ swiper: e, extendParams: s, on: t, emit: i }) {
    const a = [],
        n = V(),
        l = (f, g = {}) => {
            const u = n.MutationObserver || n.WebkitMutationObserver,
                v = new u((d) => {
                    if (e.__preventObserver__) return
                    if (d.length === 1) {
                        i('observerUpdate', d[0])
                        return
                    }
                    const h = function () {
                        i('observerUpdate', d[0])
                    }
                    n.requestAnimationFrame
                        ? n.requestAnimationFrame(h)
                        : n.setTimeout(h, 0)
                })
            ;(v.observe(f, {
                attributes: typeof g.attributes > 'u' ? !0 : g.attributes,
                childList:
                    e.isElement ||
                    (typeof g.childList > 'u' ? !0 : g).childList,
                characterData:
                    typeof g.characterData > 'u' ? !0 : g.characterData,
            }),
                a.push(v))
        },
        o = () => {
            if (e.params.observer) {
                if (e.params.observeParents) {
                    const f = Te(e.hostEl)
                    for (let g = 0; g < f.length; g += 1) l(f[g])
                }
                ;(l(e.hostEl, { childList: e.params.observeSlideChildren }),
                    l(e.wrapperEl, { attributes: !1 }))
            }
        },
        r = () => {
            ;(a.forEach((f) => {
                f.disconnect()
            }),
                a.splice(0, a.length))
        }
    ;(s({ observer: !1, observeParents: !1, observeSlideChildren: !1 }),
        t('init', o),
        t('destroy', r))
}
var je = {
    on(e, s, t) {
        const i = this
        if (!i.eventsListeners || i.destroyed || typeof s != 'function')
            return i
        const a = t ? 'unshift' : 'push'
        return (
            e.split(' ').forEach((n) => {
                ;(i.eventsListeners[n] || (i.eventsListeners[n] = []),
                    i.eventsListeners[n][a](s))
            }),
            i
        )
    },
    once(e, s, t) {
        const i = this
        if (!i.eventsListeners || i.destroyed || typeof s != 'function')
            return i
        function a(...n) {
            ;(i.off(e, a),
                a.__emitterProxy && delete a.__emitterProxy,
                s.apply(i, n))
        }
        return ((a.__emitterProxy = s), i.on(e, a, t))
    },
    onAny(e, s) {
        const t = this
        if (!t.eventsListeners || t.destroyed || typeof e != 'function')
            return t
        const i = s ? 'unshift' : 'push'
        return (
            t.eventsAnyListeners.indexOf(e) < 0 && t.eventsAnyListeners[i](e),
            t
        )
    },
    offAny(e) {
        const s = this
        if (!s.eventsListeners || s.destroyed || !s.eventsAnyListeners) return s
        const t = s.eventsAnyListeners.indexOf(e)
        return (t >= 0 && s.eventsAnyListeners.splice(t, 1), s)
    },
    off(e, s) {
        const t = this
        return (
            !t.eventsListeners ||
                t.destroyed ||
                !t.eventsListeners ||
                e.split(' ').forEach((i) => {
                    typeof s > 'u'
                        ? (t.eventsListeners[i] = [])
                        : t.eventsListeners[i] &&
                          t.eventsListeners[i].forEach((a, n) => {
                              ;(a === s ||
                                  (a.__emitterProxy &&
                                      a.__emitterProxy === s)) &&
                                  t.eventsListeners[i].splice(n, 1)
                          })
                }),
            t
        )
    },
    emit(...e) {
        const s = this
        if (!s.eventsListeners || s.destroyed || !s.eventsListeners) return s
        let t, i, a
        return (
            typeof e[0] == 'string' || Array.isArray(e[0])
                ? ((t = e[0]), (i = e.slice(1, e.length)), (a = s))
                : ((t = e[0].events), (i = e[0].data), (a = e[0].context || s)),
            i.unshift(a),
            (Array.isArray(t) ? t : t.split(' ')).forEach((l) => {
                ;(s.eventsAnyListeners &&
                    s.eventsAnyListeners.length &&
                    s.eventsAnyListeners.forEach((o) => {
                        o.apply(a, [l, ...i])
                    }),
                    s.eventsListeners &&
                        s.eventsListeners[l] &&
                        s.eventsListeners[l].forEach((o) => {
                            o.apply(a, i)
                        }))
            }),
            s
        )
    },
}
function Ye() {
    const e = this
    let s, t
    const i = e.el
    ;(typeof e.params.width < 'u' && e.params.width !== null
        ? (s = e.params.width)
        : (s = i.clientWidth),
        typeof e.params.height < 'u' && e.params.height !== null
            ? (t = e.params.height)
            : (t = i.clientHeight),
        !((s === 0 && e.isHorizontal()) || (t === 0 && e.isVertical())) &&
            ((s =
                s -
                parseInt(q(i, 'padding-left') || 0, 10) -
                parseInt(q(i, 'padding-right') || 0, 10)),
            (t =
                t -
                parseInt(q(i, 'padding-top') || 0, 10) -
                parseInt(q(i, 'padding-bottom') || 0, 10)),
            Number.isNaN(s) && (s = 0),
            Number.isNaN(t) && (t = 0),
            Object.assign(e, {
                width: s,
                height: t,
                size: e.isHorizontal() ? s : t,
            })))
}
function Xe() {
    const e = this
    function s(E, w) {
        return parseFloat(E.getPropertyValue(e.getDirectionLabel(w)) || 0)
    }
    const t = e.params,
        { wrapperEl: i, slidesEl: a, rtlTranslate: n, wrongRTL: l } = e,
        o = e.virtual && t.virtual.enabled,
        r = o ? e.virtual.slides.length : e.slides.length,
        f = H(a, `.${e.params.slideClass}, swiper-slide`),
        g = o ? e.virtual.slides.length : f.length
    let u = []
    const v = [],
        d = []
    let h = t.slidesOffsetBefore
    typeof h == 'function' && (h = t.slidesOffsetBefore.call(e))
    let y = t.slidesOffsetAfter
    typeof y == 'function' && (y = t.slidesOffsetAfter.call(e))
    const x = e.snapGrid.length,
        c = e.slidesGrid.length,
        p = e.size - h - y
    let m = t.spaceBetween,
        S = -h,
        T = 0,
        L = 0
    if (typeof p > 'u') return
    ;(typeof m == 'string' && m.indexOf('%') >= 0
        ? (m = (parseFloat(m.replace('%', '')) / 100) * p)
        : typeof m == 'string' && (m = parseFloat(m)),
        (e.virtualSize = -m - h - y),
        f.forEach((E) => {
            ;(n ? (E.style.marginLeft = '') : (E.style.marginRight = ''),
                (E.style.marginBottom = ''),
                (E.style.marginTop = ''))
        }),
        t.centeredSlides &&
            t.cssMode &&
            (W(i, '--swiper-centered-offset-before', ''),
            W(i, '--swiper-centered-offset-after', '')),
        t.cssMode &&
            (W(i, '--swiper-slides-offset-before', `${h}px`),
            W(i, '--swiper-slides-offset-after', `${y}px`)))
    const P = t.grid && t.grid.rows > 1 && e.grid
    P ? e.grid.initSlides(f) : e.grid && e.grid.unsetSlides()
    let b
    const k =
        t.slidesPerView === 'auto' &&
        t.breakpoints &&
        Object.keys(t.breakpoints).filter(
            (E) => typeof t.breakpoints[E].slidesPerView < 'u',
        ).length > 0
    for (let E = 0; E < g; E += 1) {
        b = 0
        const w = f[E]
        if (
            !(
                w &&
                (P && e.grid.updateSlide(E, w, f), q(w, 'display') === 'none')
            )
        ) {
            if (o && t.slidesPerView === 'auto')
                (t.virtual.slidesPerViewAutoSlideSize &&
                    (b = t.virtual.slidesPerViewAutoSlideSize),
                    b &&
                        w &&
                        (t.roundLengths && (b = Math.floor(b)),
                        (w.style[e.getDirectionLabel('width')] = `${b}px`)))
            else if (t.slidesPerView === 'auto') {
                k && (w.style[e.getDirectionLabel('width')] = '')
                const C = getComputedStyle(w),
                    I = w.style.transform,
                    A = w.style.webkitTransform
                if (
                    (I && (w.style.transform = 'none'),
                    A && (w.style.webkitTransform = 'none'),
                    t.roundLengths)
                )
                    b = e.isHorizontal() ? re(w, 'width') : re(w, 'height')
                else {
                    const D = s(C, 'width'),
                        _ = s(C, 'padding-left'),
                        O = s(C, 'padding-right'),
                        M = s(C, 'margin-left'),
                        z = s(C, 'margin-right'),
                        G = C.getPropertyValue('box-sizing')
                    if (G && G === 'border-box') b = D + M + z
                    else {
                        const { clientWidth: R, offsetWidth: Ie } = w
                        b = D + _ + O + M + z + (Ie - R)
                    }
                }
                ;(I && (w.style.transform = I),
                    A && (w.style.webkitTransform = A),
                    t.roundLengths && (b = Math.floor(b)))
            } else
                ((b = (p - (t.slidesPerView - 1) * m) / t.slidesPerView),
                    t.roundLengths && (b = Math.floor(b)),
                    w && (w.style[e.getDirectionLabel('width')] = `${b}px`))
            ;(w && (w.swiperSlideSize = b),
                d.push(b),
                t.centeredSlides
                    ? ((S = S + b / 2 + T / 2 + m),
                      T === 0 && E !== 0 && (S = S - p / 2 - m),
                      E === 0 && (S = S - p / 2 - m),
                      Math.abs(S) < 1 / 1e3 && (S = 0),
                      t.roundLengths && (S = Math.floor(S)),
                      L % t.slidesPerGroup === 0 && u.push(S),
                      v.push(S))
                    : (t.roundLengths && (S = Math.floor(S)),
                      (L - Math.min(e.params.slidesPerGroupSkip, L)) %
                          e.params.slidesPerGroup ===
                          0 && u.push(S),
                      v.push(S),
                      (S = S + b + m)),
                (e.virtualSize += b + m),
                (T = b),
                (L += 1))
        }
    }
    if (
        ((e.virtualSize = Math.max(e.virtualSize, p) + y),
        n &&
            l &&
            (t.effect === 'slide' || t.effect === 'coverflow') &&
            (i.style.width = `${e.virtualSize + m}px`),
        t.setWrapperSize &&
            (i.style[e.getDirectionLabel('width')] = `${e.virtualSize + m}px`),
        P && e.grid.updateWrapperSize(b, u),
        !t.centeredSlides)
    ) {
        const E = t.slidesPerView !== 'auto' && t.slidesPerView % 1 !== 0,
            w =
                t.snapToSlideEdge &&
                !t.loop &&
                (t.slidesPerView === 'auto' || E)
        let C = u.length
        if (w) {
            let A
            if (t.slidesPerView === 'auto') {
                A = 1
                let D = 0
                for (
                    let _ = d.length - 1;
                    _ >= 0 &&
                    ((D += d[_] + (_ < d.length - 1 ? m : 0)), D <= p);
                    _ -= 1
                )
                    A = d.length - _
            } else A = Math.floor(t.slidesPerView)
            C = Math.max(g - A, 0)
        }
        const I = []
        for (let A = 0; A < u.length; A += 1) {
            let D = u[A]
            ;(t.roundLengths && (D = Math.floor(D)),
                w
                    ? A <= C && I.push(D)
                    : u[A] <= e.virtualSize - p && I.push(D))
        }
        ;((u = I),
            Math.floor(e.virtualSize - p) - Math.floor(u[u.length - 1]) > 1 &&
                (w || u.push(e.virtualSize - p)))
    }
    if (o && t.loop) {
        const E = d[0] + m
        if (t.slidesPerGroup > 1) {
            const w = Math.ceil(
                    (e.virtual.slidesBefore + e.virtual.slidesAfter) /
                        t.slidesPerGroup,
                ),
                C = E * t.slidesPerGroup
            for (let I = 0; I < w; I += 1) u.push(u[u.length - 1] + C)
        }
        for (
            let w = 0;
            w < e.virtual.slidesBefore + e.virtual.slidesAfter;
            w += 1
        )
            (t.slidesPerGroup === 1 && u.push(u[u.length - 1] + E),
                v.push(v[v.length - 1] + E),
                (e.virtualSize += E))
    }
    if ((u.length === 0 && (u = [0]), m !== 0)) {
        const E =
            e.isHorizontal() && n
                ? 'marginLeft'
                : e.getDirectionLabel('marginRight')
        f.filter((w, C) =>
            !t.cssMode || t.loop ? !0 : C !== f.length - 1,
        ).forEach((w) => {
            w.style[E] = `${m}px`
        })
    }
    if (t.centeredSlides && t.centeredSlidesBounds) {
        let E = 0
        ;(d.forEach((C) => {
            E += C + (m || 0)
        }),
            (E -= m))
        const w = E > p ? E - p : 0
        u = u.map((C) => (C <= 0 ? -h : C > w ? w + y : C))
    }
    if (t.centerInsufficientSlides) {
        let E = 0
        if (
            (d.forEach((w) => {
                E += w + (m || 0)
            }),
            (E -= m),
            E < p)
        ) {
            const w = (p - E) / 2
            ;(u.forEach((C, I) => {
                u[I] = C - w
            }),
                v.forEach((C, I) => {
                    v[I] = C + w
                }))
        }
    }
    if (
        (Object.assign(e, {
            slides: f,
            snapGrid: u,
            slidesGrid: v,
            slidesSizesGrid: d,
        }),
        t.centeredSlides && t.cssMode && !t.centeredSlidesBounds)
    ) {
        ;(W(i, '--swiper-centered-offset-before', `${-u[0]}px`),
            W(
                i,
                '--swiper-centered-offset-after',
                `${e.size / 2 - d[d.length - 1] / 2}px`,
            ))
        const E = -e.snapGrid[0],
            w = -e.slidesGrid[0]
        ;((e.snapGrid = e.snapGrid.map((C) => C + E)),
            (e.slidesGrid = e.slidesGrid.map((C) => C + w)))
    }
    if (
        (g !== r && e.emit('slidesLengthChange'),
        u.length !== x &&
            (e.params.watchOverflow && e.checkOverflow(),
            e.emit('snapGridLengthChange')),
        v.length !== c && e.emit('slidesGridLengthChange'),
        t.watchSlidesProgress && e.updateSlidesOffset(),
        e.emit('slidesUpdated'),
        !o && !t.cssMode && (t.effect === 'slide' || t.effect === 'fade'))
    ) {
        const E = `${t.containerModifierClass}backface-hidden`,
            w = e.el.classList.contains(E)
        g <= t.maxBackfaceHiddenSlides
            ? w || e.el.classList.add(E)
            : w && e.el.classList.remove(E)
    }
}
function Ue(e) {
    const s = this,
        t = [],
        i = s.virtual && s.params.virtual.enabled
    let a = 0,
        n
    typeof e == 'number'
        ? s.setTransition(e)
        : e === !0 && s.setTransition(s.params.speed)
    const l = (o) => (i ? s.slides[s.getSlideIndexByData(o)] : s.slides[o])
    if (s.params.slidesPerView !== 'auto' && s.params.slidesPerView > 1)
        if (s.params.centeredSlides)
            (s.visibleSlides || []).forEach((o) => {
                t.push(o)
            })
        else
            for (n = 0; n < Math.ceil(s.params.slidesPerView); n += 1) {
                const o = s.activeIndex + n
                if (o > s.slides.length && !i) break
                t.push(l(o))
            }
    else t.push(l(s.activeIndex))
    for (n = 0; n < t.length; n += 1)
        if (typeof t[n] < 'u') {
            const o = t[n].offsetHeight
            a = o > a ? o : a
        }
    ;(a || a === 0) && (s.wrapperEl.style.height = `${a}px`)
}
function Ke() {
    const e = this,
        s = e.slides,
        t = e.isElement
            ? e.isHorizontal()
                ? e.wrapperEl.offsetLeft
                : e.wrapperEl.offsetTop
            : 0
    for (let i = 0; i < s.length; i += 1)
        s[i].swiperSlideOffset =
            (e.isHorizontal() ? s[i].offsetLeft : s[i].offsetTop) -
            t -
            e.cssOverflowAdjustment()
}
const fe = (e, s, t) => {
    s && !e.classList.contains(t)
        ? e.classList.add(t)
        : !s && e.classList.contains(t) && e.classList.remove(t)
}
function Je(e = (this && this.translate) || 0) {
    const s = this,
        t = s.params,
        { slides: i, rtlTranslate: a, snapGrid: n } = s
    if (i.length === 0) return
    typeof i[0].swiperSlideOffset > 'u' && s.updateSlidesOffset()
    let l = -e
    ;(a && (l = e), (s.visibleSlidesIndexes = []), (s.visibleSlides = []))
    let o = t.spaceBetween
    typeof o == 'string' && o.indexOf('%') >= 0
        ? (o = (parseFloat(o.replace('%', '')) / 100) * s.size)
        : typeof o == 'string' && (o = parseFloat(o))
    for (let r = 0; r < i.length; r += 1) {
        const f = i[r]
        let g = f.swiperSlideOffset
        t.cssMode && t.centeredSlides && (g -= i[0].swiperSlideOffset)
        const u =
                (l + (t.centeredSlides ? s.minTranslate() : 0) - g) /
                (f.swiperSlideSize + o),
            v =
                (l - n[0] + (t.centeredSlides ? s.minTranslate() : 0) - g) /
                (f.swiperSlideSize + o),
            d = -(l - g),
            h = d + s.slidesSizesGrid[r],
            y = d >= 0 && d <= s.size - s.slidesSizesGrid[r],
            x =
                (d >= 0 && d < s.size - 1) ||
                (h > 1 && h <= s.size) ||
                (d <= 0 && h >= s.size)
        ;(x && (s.visibleSlides.push(f), s.visibleSlidesIndexes.push(r)),
            fe(f, x, t.slideVisibleClass),
            fe(f, y, t.slideFullyVisibleClass),
            (f.progress = a ? -u : u),
            (f.originalProgress = a ? -v : v))
    }
}
function Qe(e) {
    const s = this
    if (typeof e > 'u') {
        const g = s.rtlTranslate ? -1 : 1
        e = (s && s.translate && s.translate * g) || 0
    }
    const t = s.params,
        i = s.maxTranslate() - s.minTranslate()
    let { progress: a, isBeginning: n, isEnd: l, progressLoop: o } = s
    const r = n,
        f = l
    if (i === 0) ((a = 0), (n = !0), (l = !0))
    else {
        a = (e - s.minTranslate()) / i
        const g = Math.abs(e - s.minTranslate()) < 1,
            u = Math.abs(e - s.maxTranslate()) < 1
        ;((n = g || a <= 0), (l = u || a >= 1), g && (a = 0), u && (a = 1))
    }
    if (t.loop) {
        const g = s.getSlideIndexByData(0),
            u = s.getSlideIndexByData(s.slides.length - 1),
            v = s.slidesGrid[g],
            d = s.slidesGrid[u],
            h = s.slidesGrid[s.slidesGrid.length - 1],
            y = Math.abs(e)
        ;(y >= v ? (o = (y - v) / h) : (o = (y + h - d) / h), o > 1 && (o -= 1))
    }
    ;(Object.assign(s, {
        progress: a,
        progressLoop: o,
        isBeginning: n,
        isEnd: l,
    }),
        (t.watchSlidesProgress || (t.centeredSlides && t.autoHeight)) &&
            s.updateSlidesProgress(e),
        n && !r && s.emit('reachBeginning toEdge'),
        l && !f && s.emit('reachEnd toEdge'),
        ((r && !n) || (f && !l)) && s.emit('fromEdge'),
        s.emit('progress', a))
}
const se = (e, s, t) => {
    s && !e.classList.contains(t)
        ? e.classList.add(t)
        : !s && e.classList.contains(t) && e.classList.remove(t)
}
function Ze() {
    const e = this,
        { slides: s, params: t, slidesEl: i, activeIndex: a } = e,
        n = e.virtual && t.virtual.enabled,
        l = e.grid && t.grid && t.grid.rows > 1,
        o = (u) => H(i, `.${t.slideClass}${u}, swiper-slide${u}`)[0]
    let r, f, g
    if (n)
        if (t.loop) {
            let u = a - e.virtual.slidesBefore
            ;(u < 0 && (u = e.virtual.slides.length + u),
                u >= e.virtual.slides.length && (u -= e.virtual.slides.length),
                (r = o(`[data-swiper-slide-index="${u}"]`)))
        } else r = o(`[data-swiper-slide-index="${a}"]`)
    else
        l
            ? ((r = s.find((u) => u.column === a)),
              (g = s.find((u) => u.column === a + 1)),
              (f = s.find((u) => u.column === a - 1)))
            : (r = s[a])
    ;(r &&
        (l ||
            ((g = Fe(r, `.${t.slideClass}, swiper-slide`)[0]),
            t.loop && !g && (g = s[0]),
            (f = $e(r, `.${t.slideClass}, swiper-slide`)[0]),
            t.loop && !f === 0 && (f = s[s.length - 1]))),
        s.forEach((u) => {
            ;(se(u, u === r, t.slideActiveClass),
                se(u, u === g, t.slideNextClass),
                se(u, u === f, t.slidePrevClass))
        }),
        e.emitSlidesClasses())
}
const X = (e, s) => {
        if (!e || e.destroyed || !e.params) return
        const t = () =>
                e.isElement ? 'swiper-slide' : `.${e.params.slideClass}`,
            i = s.closest(t())
        if (i) {
            let a = i.querySelector(`.${e.params.lazyPreloaderClass}`)
            ;(!a &&
                e.isElement &&
                (i.shadowRoot
                    ? (a = i.shadowRoot.querySelector(
                          `.${e.params.lazyPreloaderClass}`,
                      ))
                    : requestAnimationFrame(() => {
                          i.shadowRoot &&
                              ((a = i.shadowRoot.querySelector(
                                  `.${e.params.lazyPreloaderClass}`,
                              )),
                              a && !a.lazyPreloaderManaged && a.remove())
                      })),
                a && !a.lazyPreloaderManaged && a.remove())
        }
    },
    ie = (e, s) => {
        if (!e.slides[s]) return
        const t = e.slides[s].querySelector('[loading="lazy"]')
        t && t.removeAttribute('loading')
    },
    oe = (e) => {
        if (!e || e.destroyed || !e.params) return
        let s = e.params.lazyPreloadPrevNext
        const t = e.slides.length
        if (!t || !s || s < 0) return
        s = Math.min(s, t)
        const i =
                e.params.slidesPerView === 'auto'
                    ? e.slidesPerViewDynamic()
                    : Math.ceil(e.params.slidesPerView),
            a = e.activeIndex
        if (e.params.grid && e.params.grid.rows > 1) {
            const l = a,
                o = [l - s]
            ;(o.push(...Array.from({ length: s }).map((r, f) => l + i + f)),
                e.slides.forEach((r, f) => {
                    o.includes(r.column) && ie(e, f)
                }))
            return
        }
        const n = a + i - 1
        if (e.params.rewind || e.params.loop)
            for (let l = a - s; l <= n + s; l += 1) {
                const o = ((l % t) + t) % t
                ;(o < a || o > n) && ie(e, o)
            }
        else
            for (
                let l = Math.max(a - s, 0);
                l <= Math.min(n + s, t - 1);
                l += 1
            )
                l !== a && (l > n || l < a) && ie(e, l)
    }
function et(e) {
    const { slidesGrid: s, params: t } = e,
        i = e.rtlTranslate ? e.translate : -e.translate
    let a
    for (let n = 0; n < s.length; n += 1)
        typeof s[n + 1] < 'u'
            ? i >= s[n] && i < s[n + 1] - (s[n + 1] - s[n]) / 2
                ? (a = n)
                : i >= s[n] && i < s[n + 1] && (a = n + 1)
            : i >= s[n] && (a = n)
    return (t.normalizeSlideIndex && (a < 0 || typeof a > 'u') && (a = 0), a)
}
function tt(e) {
    const s = this,
        t = s.rtlTranslate ? s.translate : -s.translate,
        {
            snapGrid: i,
            params: a,
            activeIndex: n,
            realIndex: l,
            snapIndex: o,
        } = s
    let r = e,
        f
    const g = (d) => {
        let h = d - s.virtual.slidesBefore
        return (
            h < 0 && (h = s.virtual.slides.length + h),
            h >= s.virtual.slides.length && (h -= s.virtual.slides.length),
            h
        )
    }
    if ((typeof r > 'u' && (r = et(s)), i.indexOf(t) >= 0)) f = i.indexOf(t)
    else {
        const d = Math.min(a.slidesPerGroupSkip, r)
        f = d + Math.floor((r - d) / a.slidesPerGroup)
    }
    if ((f >= i.length && (f = i.length - 1), r === n && !s.params.loop)) {
        f !== o && ((s.snapIndex = f), s.emit('snapIndexChange'))
        return
    }
    if (r === n && s.params.loop && s.virtual && s.params.virtual.enabled) {
        s.realIndex = g(r)
        return
    }
    const u = s.grid && a.grid && a.grid.rows > 1
    let v
    if (s.virtual && a.virtual.enabled) a.loop ? (v = g(r)) : (v = r)
    else if (u) {
        const d = s.slides.find((y) => y.column === r)
        let h = parseInt(d.getAttribute('data-swiper-slide-index'), 10)
        ;(Number.isNaN(h) && (h = Math.max(s.slides.indexOf(d), 0)),
            (v = Math.floor(h / a.grid.rows)))
    } else if (s.slides[r]) {
        const d = s.slides[r].getAttribute('data-swiper-slide-index')
        d ? (v = parseInt(d, 10)) : (v = r)
    } else v = r
    ;(Object.assign(s, {
        previousSnapIndex: o,
        snapIndex: f,
        previousRealIndex: l,
        realIndex: v,
        previousIndex: n,
        activeIndex: r,
    }),
        s.initialized && oe(s),
        s.emit('activeIndexChange'),
        s.emit('snapIndexChange'),
        (s.initialized || s.params.runCallbacksOnInit) &&
            (l !== v && s.emit('realIndexChange'), s.emit('slideChange')))
}
function st(e, s) {
    const t = this,
        i = t.params
    let a = e.closest(`.${i.slideClass}, swiper-slide`)
    !a &&
        t.isElement &&
        s &&
        s.length > 1 &&
        s.includes(e) &&
        [...s.slice(s.indexOf(e) + 1, s.length)].forEach((o) => {
            !a &&
                o.matches &&
                o.matches(`.${i.slideClass}, swiper-slide`) &&
                (a = o)
        })
    let n = !1,
        l
    if (a) {
        for (let o = 0; o < t.slides.length; o += 1)
            if (t.slides[o] === a) {
                ;((n = !0), (l = o))
                break
            }
    }
    if (a && n)
        ((t.clickedSlide = a),
            t.virtual && t.params.virtual.enabled
                ? (t.clickedIndex = parseInt(
                      a.getAttribute('data-swiper-slide-index'),
                      10,
                  ))
                : (t.clickedIndex = l))
    else {
        ;((t.clickedSlide = void 0), (t.clickedIndex = void 0))
        return
    }
    i.slideToClickedSlide &&
        t.clickedIndex !== void 0 &&
        t.clickedIndex !== t.activeIndex &&
        t.slideToClickedSlide()
}
var it = {
    updateSize: Ye,
    updateSlides: Xe,
    updateAutoHeight: Ue,
    updateSlidesOffset: Ke,
    updateSlidesProgress: Je,
    updateProgress: Qe,
    updateSlidesClasses: Ze,
    updateActiveIndex: tt,
    updateClickedSlide: st,
}
function nt(e = this.isHorizontal() ? 'x' : 'y') {
    const s = this,
        { params: t, rtlTranslate: i, translate: a, wrapperEl: n } = s
    if (t.virtualTranslate) return i ? -a : a
    if (t.cssMode) return a
    let l = De(n, e)
    return ((l += s.cssOverflowAdjustment()), i && (l = -l), l || 0)
}
function at(e, s) {
    const t = this,
        { rtlTranslate: i, params: a, wrapperEl: n, progress: l } = t
    let o = 0,
        r = 0
    const f = 0
    ;(t.isHorizontal() ? (o = i ? -e : e) : (r = e),
        a.roundLengths && ((o = Math.floor(o)), (r = Math.floor(r))),
        (t.previousTranslate = t.translate),
        (t.translate = t.isHorizontal() ? o : r),
        a.cssMode
            ? (n[t.isHorizontal() ? 'scrollLeft' : 'scrollTop'] =
                  t.isHorizontal() ? -o : -r)
            : a.virtualTranslate ||
              (t.isHorizontal()
                  ? (o -= t.cssOverflowAdjustment())
                  : (r -= t.cssOverflowAdjustment()),
              (n.style.transform = `translate3d(${o}px, ${r}px, ${f}px)`)))
    let g
    const u = t.maxTranslate() - t.minTranslate()
    ;(u === 0 ? (g = 0) : (g = (e - t.minTranslate()) / u),
        g !== l && t.updateProgress(e),
        t.emit('setTranslate', t.translate, s))
}
function rt() {
    return -this.snapGrid[0]
}
function lt() {
    return -this.snapGrid[this.snapGrid.length - 1]
}
function ot(e = 0, s = this.params.speed, t = !0, i = !0, a) {
    const n = this,
        { params: l, wrapperEl: o } = n
    if (n.animating && l.preventInteractionOnTransition) return !1
    const r = n.minTranslate(),
        f = n.maxTranslate()
    let g
    if (
        (i && e > r ? (g = r) : i && e < f ? (g = f) : (g = e),
        n.updateProgress(g),
        l.cssMode)
    ) {
        const u = n.isHorizontal()
        if (s === 0) o[u ? 'scrollLeft' : 'scrollTop'] = -g
        else {
            if (!n.support.smoothScroll)
                return (
                    Se({
                        swiper: n,
                        targetPosition: -g,
                        side: u ? 'left' : 'top',
                    }),
                    !0
                )
            o.scrollTo({ [u ? 'left' : 'top']: -g, behavior: 'smooth' })
        }
        return !0
    }
    return (
        s === 0
            ? (n.setTransition(0),
              n.setTranslate(g),
              t &&
                  (n.emit('beforeTransitionStart', s, a),
                  n.emit('transitionEnd')))
            : (n.setTransition(s),
              n.setTranslate(g),
              t &&
                  (n.emit('beforeTransitionStart', s, a),
                  n.emit('transitionStart')),
              n.animating ||
                  ((n.animating = !0),
                  n.onTranslateToWrapperTransitionEnd ||
                      (n.onTranslateToWrapperTransitionEnd = function (v) {
                          !n ||
                              n.destroyed ||
                              (v.target === this &&
                                  (n.wrapperEl.removeEventListener(
                                      'transitionend',
                                      n.onTranslateToWrapperTransitionEnd,
                                  ),
                                  (n.onTranslateToWrapperTransitionEnd = null),
                                  delete n.onTranslateToWrapperTransitionEnd,
                                  (n.animating = !1),
                                  t && n.emit('transitionEnd')))
                      }),
                  n.wrapperEl.addEventListener(
                      'transitionend',
                      n.onTranslateToWrapperTransitionEnd,
                  ))),
        !0
    )
}
var dt = {
    getTranslate: nt,
    setTranslate: at,
    minTranslate: rt,
    maxTranslate: lt,
    translateTo: ot,
}
function ct(e, s) {
    const t = this
    ;(t.params.cssMode ||
        ((t.wrapperEl.style.transitionDuration = `${e}ms`),
        (t.wrapperEl.style.transitionDelay = e === 0 ? '0ms' : '')),
        t.emit('setTransition', e, s))
}
function Ce({ swiper: e, runCallbacks: s, direction: t, step: i }) {
    const { activeIndex: a, previousIndex: n } = e
    let l = t
    ;(l || (a > n ? (l = 'next') : a < n ? (l = 'prev') : (l = 'reset')),
        e.emit(`transition${i}`),
        s && l === 'reset'
            ? e.emit(`slideResetTransition${i}`)
            : s &&
              a !== n &&
              (e.emit(`slideChangeTransition${i}`),
              l === 'next'
                  ? e.emit(`slideNextTransition${i}`)
                  : e.emit(`slidePrevTransition${i}`)))
}
function ft(e = !0, s) {
    const t = this,
        { params: i } = t
    i.cssMode ||
        (i.autoHeight && t.updateAutoHeight(),
        Ce({ swiper: t, runCallbacks: e, direction: s, step: 'Start' }))
}
function ut(e = !0, s) {
    const t = this,
        { params: i } = t
    ;((t.animating = !1),
        !i.cssMode &&
            (t.setTransition(0),
            Ce({ swiper: t, runCallbacks: e, direction: s, step: 'End' })))
}
var pt = { setTransition: ct, transitionStart: ft, transitionEnd: ut }
function mt(e = 0, s, t = !0, i, a) {
    typeof e == 'string' && (e = parseInt(e, 10))
    const n = this
    let l = e
    l < 0 && (l = 0)
    const {
        params: o,
        snapGrid: r,
        slidesGrid: f,
        previousIndex: g,
        activeIndex: u,
        rtlTranslate: v,
        wrapperEl: d,
        enabled: h,
    } = n
    if (
        (!h && !i && !a) ||
        n.destroyed ||
        (n.animating && o.preventInteractionOnTransition)
    )
        return !1
    typeof s > 'u' && (s = n.params.speed)
    const y = Math.min(n.params.slidesPerGroupSkip, l)
    let x = y + Math.floor((l - y) / n.params.slidesPerGroup)
    x >= r.length && (x = r.length - 1)
    const c = -r[x]
    if (o.normalizeSlideIndex)
        for (let P = 0; P < f.length; P += 1) {
            const b = -Math.floor(c * 100),
                k = Math.floor(f[P] * 100),
                E = Math.floor(f[P + 1] * 100)
            typeof f[P + 1] < 'u'
                ? b >= k && b < E - (E - k) / 2
                    ? (l = P)
                    : b >= k && b < E && (l = P + 1)
                : b >= k && (l = P)
        }
    if (
        n.initialized &&
        l !== u &&
        ((!n.allowSlideNext &&
            (v
                ? c > n.translate && c > n.minTranslate()
                : c < n.translate && c < n.minTranslate())) ||
            (!n.allowSlidePrev &&
                c > n.translate &&
                c > n.maxTranslate() &&
                (u || 0) !== l))
    )
        return !1
    ;(l !== (g || 0) && t && n.emit('beforeSlideChangeStart'),
        n.updateProgress(c))
    let p
    l > u ? (p = 'next') : l < u ? (p = 'prev') : (p = 'reset')
    const m = n.virtual && n.params.virtual.enabled
    if (!(m && a) && ((v && -c === n.translate) || (!v && c === n.translate)))
        return (
            n.updateActiveIndex(l),
            o.autoHeight && n.updateAutoHeight(),
            n.updateSlidesClasses(),
            o.effect !== 'slide' && n.setTranslate(c),
            p !== 'reset' && (n.transitionStart(t, p), n.transitionEnd(t, p)),
            !1
        )
    if (o.cssMode) {
        const P = n.isHorizontal(),
            b = v ? c : -c
        if (s === 0)
            (m &&
                ((n.wrapperEl.style.scrollSnapType = 'none'),
                (n._immediateVirtual = !0)),
                m && !n._cssModeVirtualInitialSet && n.params.initialSlide > 0
                    ? ((n._cssModeVirtualInitialSet = !0),
                      requestAnimationFrame(() => {
                          d[P ? 'scrollLeft' : 'scrollTop'] = b
                      }))
                    : (d[P ? 'scrollLeft' : 'scrollTop'] = b),
                m &&
                    requestAnimationFrame(() => {
                        ;((n.wrapperEl.style.scrollSnapType = ''),
                            (n._immediateVirtual = !1))
                    }))
        else {
            if (!n.support.smoothScroll)
                return (
                    Se({
                        swiper: n,
                        targetPosition: b,
                        side: P ? 'left' : 'top',
                    }),
                    !0
                )
            d.scrollTo({ [P ? 'left' : 'top']: b, behavior: 'smooth' })
        }
        return !0
    }
    const L = we().isSafari
    return (
        m && !a && L && n.isElement && n.virtual.update(!1, !1, l),
        n.setTransition(s),
        n.setTranslate(c),
        n.updateActiveIndex(l),
        n.updateSlidesClasses(),
        n.emit('beforeTransitionStart', s, i),
        n.transitionStart(t, p),
        s === 0
            ? n.transitionEnd(t, p)
            : n.animating ||
              ((n.animating = !0),
              n.onSlideToWrapperTransitionEnd ||
                  (n.onSlideToWrapperTransitionEnd = function (b) {
                      !n ||
                          n.destroyed ||
                          (b.target === this &&
                              (n.wrapperEl.removeEventListener(
                                  'transitionend',
                                  n.onSlideToWrapperTransitionEnd,
                              ),
                              (n.onSlideToWrapperTransitionEnd = null),
                              delete n.onSlideToWrapperTransitionEnd,
                              n.transitionEnd(t, p)))
                  }),
              n.wrapperEl.addEventListener(
                  'transitionend',
                  n.onSlideToWrapperTransitionEnd,
              )),
        !0
    )
}
function ht(e = 0, s, t = !0, i) {
    typeof e == 'string' && (e = parseInt(e, 10))
    const a = this
    if (a.destroyed) return
    typeof s > 'u' && (s = a.params.speed)
    const n = a.grid && a.params.grid && a.params.grid.rows > 1
    let l = e
    if (a.params.loop)
        if (a.virtual && a.params.virtual.enabled)
            l = l + a.virtual.slidesBefore
        else {
            let o
            if (n) {
                const y = l * a.params.grid.rows
                o = a.slides.find(
                    (x) => x.getAttribute('data-swiper-slide-index') * 1 === y,
                ).column
            } else o = a.getSlideIndexByData(l)
            const r = n
                    ? Math.ceil(a.slides.length / a.params.grid.rows)
                    : a.slides.length,
                {
                    centeredSlides: f,
                    slidesOffsetBefore: g,
                    slidesOffsetAfter: u,
                } = a.params,
                v = f || !!g || !!u
            let d = a.params.slidesPerView
            d === 'auto'
                ? (d = a.slidesPerViewDynamic())
                : ((d = Math.ceil(parseFloat(a.params.slidesPerView, 10))),
                  v && d % 2 === 0 && (d = d + 1))
            let h = r - o < d
            if (
                (v && (h = h || o < Math.ceil(d / 2)),
                i && v && a.params.slidesPerView !== 'auto' && !n && (h = !1),
                h)
            ) {
                const y = v
                    ? o < a.activeIndex
                        ? 'prev'
                        : 'next'
                    : o - a.activeIndex - 1 < a.params.slidesPerView
                      ? 'next'
                      : 'prev'
                a.loopFix({
                    direction: y,
                    slideTo: !0,
                    activeSlideIndex: y === 'next' ? o + 1 : o - r + 1,
                    slideRealIndex: y === 'next' ? a.realIndex : void 0,
                })
            }
            if (n) {
                const y = l * a.params.grid.rows
                l = a.slides.find(
                    (x) => x.getAttribute('data-swiper-slide-index') * 1 === y,
                ).column
            } else l = a.getSlideIndexByData(l)
        }
    return (
        requestAnimationFrame(() => {
            a.slideTo(l, s, t, i)
        }),
        a
    )
}
function gt(e, s = !0, t) {
    const i = this,
        { enabled: a, params: n, animating: l } = i
    if (!a || i.destroyed) return i
    typeof e > 'u' && (e = i.params.speed)
    let o = n.slidesPerGroup
    n.slidesPerView === 'auto' &&
        n.slidesPerGroup === 1 &&
        n.slidesPerGroupAuto &&
        (o = Math.max(i.slidesPerViewDynamic('current', !0), 1))
    const r = i.activeIndex < n.slidesPerGroupSkip ? 1 : o,
        f = i.virtual && n.virtual.enabled
    if (n.loop) {
        if (l && !f && n.loopPreventsSliding) return !1
        if (
            (i.loopFix({ direction: 'next' }),
            (i._clientLeft = i.wrapperEl.clientLeft),
            i.activeIndex === i.slides.length - 1 && n.cssMode)
        )
            return (
                requestAnimationFrame(() => {
                    i.slideTo(i.activeIndex + r, e, s, t)
                }),
                !0
            )
    }
    return n.rewind && i.isEnd
        ? i.slideTo(0, e, s, t)
        : i.slideTo(i.activeIndex + r, e, s, t)
}
function vt(e, s = !0, t) {
    const i = this,
        {
            params: a,
            snapGrid: n,
            slidesGrid: l,
            rtlTranslate: o,
            enabled: r,
            animating: f,
        } = i
    if (!r || i.destroyed) return i
    typeof e > 'u' && (e = i.params.speed)
    const g = i.virtual && a.virtual.enabled
    if (a.loop) {
        if (f && !g && a.loopPreventsSliding) return !1
        ;(i.loopFix({ direction: 'prev' }),
            (i._clientLeft = i.wrapperEl.clientLeft))
    }
    const u = o ? i.translate : -i.translate
    function v(p) {
        return p < 0 ? -Math.floor(Math.abs(p)) : Math.floor(p)
    }
    const d = v(u),
        h = n.map((p) => v(p)),
        y = a.freeMode && a.freeMode.enabled
    let x = n[h.indexOf(d) - 1]
    if (typeof x > 'u' && (a.cssMode || y)) {
        let p
        ;(n.forEach((m, S) => {
            d >= m && (p = S)
        }),
            typeof p < 'u' && (x = y ? n[p] : n[p > 0 ? p - 1 : p]))
    }
    let c = 0
    if (
        (typeof x < 'u' &&
            ((c = l.indexOf(x)),
            c < 0 && (c = i.activeIndex - 1),
            a.slidesPerView === 'auto' &&
                a.slidesPerGroup === 1 &&
                a.slidesPerGroupAuto &&
                ((c = c - i.slidesPerViewDynamic('previous', !0) + 1),
                (c = Math.max(c, 0)))),
        a.rewind && i.isBeginning)
    ) {
        const p =
            i.params.virtual && i.params.virtual.enabled && i.virtual
                ? i.virtual.slides.length - 1
                : i.slides.length - 1
        return i.slideTo(p, e, s, t)
    } else if (a.loop && i.activeIndex === 0 && a.cssMode)
        return (
            requestAnimationFrame(() => {
                i.slideTo(c, e, s, t)
            }),
            !0
        )
    return i.slideTo(c, e, s, t)
}
function yt(e, s = !0, t) {
    const i = this
    if (!i.destroyed)
        return (
            typeof e > 'u' && (e = i.params.speed),
            i.slideTo(i.activeIndex, e, s, t)
        )
}
function St(e, s = !0, t, i = 0.5) {
    const a = this
    if (a.destroyed) return
    typeof e > 'u' && (e = a.params.speed)
    let n = a.activeIndex
    const l = Math.min(a.params.slidesPerGroupSkip, n),
        o = l + Math.floor((n - l) / a.params.slidesPerGroup),
        r = a.rtlTranslate ? a.translate : -a.translate
    if (r >= a.snapGrid[o]) {
        const f = a.snapGrid[o],
            g = a.snapGrid[o + 1]
        r - f > (g - f) * i && (n += a.params.slidesPerGroup)
    } else {
        const f = a.snapGrid[o - 1],
            g = a.snapGrid[o]
        r - f <= (g - f) * i && (n -= a.params.slidesPerGroup)
    }
    return (
        (n = Math.max(n, 0)),
        (n = Math.min(n, a.slidesGrid.length - 1)),
        a.slideTo(n, e, s, t)
    )
}
function bt() {
    const e = this
    if (e.destroyed) return
    const { params: s, slidesEl: t } = e,
        i =
            s.slidesPerView === 'auto'
                ? e.slidesPerViewDynamic()
                : s.slidesPerView
    let a = e.getSlideIndexWhenGrid(e.clickedIndex),
        n
    const l = e.isElement ? 'swiper-slide' : `.${s.slideClass}`,
        o = e.grid && e.params.grid && e.params.grid.rows > 1
    if (s.loop) {
        if (e.animating) return
        ;((n = parseInt(
            e.clickedSlide.getAttribute('data-swiper-slide-index'),
            10,
        )),
            s.centeredSlides
                ? e.slideToLoop(n)
                : a >
                    (o
                        ? (e.slides.length - i) / 2 - (e.params.grid.rows - 1)
                        : e.slides.length - i)
                  ? (e.loopFix(),
                    (a = e.getSlideIndex(
                        H(t, `${l}[data-swiper-slide-index="${n}"]`)[0],
                    )),
                    ye(() => {
                        e.slideTo(a)
                    }))
                  : e.slideTo(a))
    } else e.slideTo(a)
}
var Tt = {
    slideTo: mt,
    slideToLoop: ht,
    slideNext: gt,
    slidePrev: vt,
    slideReset: yt,
    slideToClosest: St,
    slideToClickedSlide: bt,
}
function xt(e, s) {
    const t = this,
        { params: i, slidesEl: a } = t
    if (!i.loop || (t.virtual && t.params.virtual.enabled)) return
    const n = () => {
            H(a, `.${i.slideClass}, swiper-slide`).forEach((h, y) => {
                h.setAttribute('data-swiper-slide-index', y)
            })
        },
        l = () => {
            const d = H(a, `.${i.slideBlankClass}`)
            ;(d.forEach((h) => {
                h.remove()
            }),
                d.length > 0 && (t.recalcSlides(), t.updateSlides()))
        },
        o = t.grid && i.grid && i.grid.rows > 1
    i.loopAddBlankSlides && (i.slidesPerGroup > 1 || o) && l()
    const r = i.slidesPerGroup * (o ? i.grid.rows : 1),
        f = t.slides.length % r !== 0,
        g = o && t.slides.length % i.grid.rows !== 0,
        u = (d) => {
            for (let h = 0; h < d; h += 1) {
                const y = t.isElement
                    ? J('swiper-slide', [i.slideBlankClass])
                    : J('div', [i.slideClass, i.slideBlankClass])
                t.slidesEl.append(y)
            }
        }
    if (f) {
        if (i.loopAddBlankSlides) {
            const d = r - (t.slides.length % r)
            ;(u(d), t.recalcSlides(), t.updateSlides())
        } else
            K(
                'Swiper Loop Warning: The number of slides is not even to slidesPerGroup, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)',
            )
        n()
    } else if (g) {
        if (i.loopAddBlankSlides) {
            const d = i.grid.rows - (t.slides.length % i.grid.rows)
            ;(u(d), t.recalcSlides(), t.updateSlides())
        } else
            K(
                'Swiper Loop Warning: The number of slides is not even to grid.rows, loop mode may not function properly. You need to add more slides (or make duplicates, or empty slides)',
            )
        n()
    } else n()
    const v =
        i.centeredSlides || !!i.slidesOffsetBefore || !!i.slidesOffsetAfter
    t.loopFix({ slideRealIndex: e, direction: v ? void 0 : 'next', initial: s })
}
function Et({
    slideRealIndex: e,
    slideTo: s = !0,
    direction: t,
    setTranslate: i,
    activeSlideIndex: a,
    initial: n,
    byController: l,
    byMousewheel: o,
} = {}) {
    const r = this
    if (!r.params.loop) return
    r.emit('beforeLoopFix')
    const {
            slides: f,
            allowSlidePrev: g,
            allowSlideNext: u,
            slidesEl: v,
            params: d,
        } = r,
        {
            centeredSlides: h,
            slidesOffsetBefore: y,
            slidesOffsetAfter: x,
            initialSlide: c,
        } = d,
        p = h || !!y || !!x
    if (
        ((r.allowSlidePrev = !0),
        (r.allowSlideNext = !0),
        r.virtual && d.virtual.enabled)
    ) {
        ;(s &&
            (!p && r.snapIndex === 0
                ? r.slideTo(r.virtual.slides.length, 0, !1, !0)
                : p && r.snapIndex < d.slidesPerView
                  ? r.slideTo(r.virtual.slides.length + r.snapIndex, 0, !1, !0)
                  : r.snapIndex === r.snapGrid.length - 1 &&
                    r.slideTo(r.virtual.slidesBefore, 0, !1, !0)),
            (r.allowSlidePrev = g),
            (r.allowSlideNext = u),
            r.emit('loopFix'))
        return
    }
    let m = d.slidesPerView
    m === 'auto'
        ? (m = r.slidesPerViewDynamic())
        : ((m = Math.ceil(parseFloat(d.slidesPerView, 10))),
          p && m % 2 === 0 && (m = m + 1))
    const S = d.slidesPerGroupAuto ? m : d.slidesPerGroup
    let T = p ? Math.max(S, Math.ceil(m / 2)) : S
    ;(T % S !== 0 && (T += S - (T % S)),
        (T += d.loopAdditionalSlides),
        (r.loopedSlides = T))
    const L = r.grid && d.grid && d.grid.rows > 1
    f.length < m + T || (r.params.effect === 'cards' && f.length < m + T * 2)
        ? K(
              'Swiper Loop Warning: The number of slides is not enough for loop mode, it will be disabled or not function properly. You need to add more slides (or make duplicates) or lower the values of slidesPerView and slidesPerGroup parameters',
          )
        : L &&
          d.grid.fill === 'row' &&
          K(
              'Swiper Loop Warning: Loop mode is not compatible with grid.fill = `row`',
          )
    const P = [],
        b = [],
        k = L ? Math.ceil(f.length / d.grid.rows) : f.length,
        E = n && k - c < m && !p
    let w = E ? c : r.activeIndex
    typeof a > 'u'
        ? (a = r.getSlideIndex(
              f.find((M) => M.classList.contains(d.slideActiveClass)),
          ))
        : (w = a)
    const C = t === 'next' || !t,
        I = t === 'prev' || !t
    let A = 0,
        D = 0
    const O = (L ? f[a].column : a) + (p && typeof i > 'u' ? -m / 2 + 0.5 : 0)
    if (O < T) {
        A = Math.max(T - O, S)
        for (let M = 0; M < T - O; M += 1) {
            const z = M - Math.floor(M / k) * k
            if (L) {
                const G = k - z - 1
                for (let R = f.length - 1; R >= 0; R -= 1)
                    f[R].column === G && P.push(R)
            } else P.push(k - z - 1)
        }
    } else if (O + m > k - T) {
        ;((D = Math.max(O - (k - T * 2), S)),
            E && (D = Math.max(D, m - k + c + 1)))
        for (let M = 0; M < D; M += 1) {
            const z = M - Math.floor(M / k) * k
            L
                ? f.forEach((G, R) => {
                      G.column === z && b.push(R)
                  })
                : b.push(z)
        }
    }
    if (
        ((r.__preventObserver__ = !0),
        requestAnimationFrame(() => {
            r.__preventObserver__ = !1
        }),
        r.params.effect === 'cards' &&
            f.length < m + T * 2 &&
            (b.includes(a) && b.splice(b.indexOf(a), 1),
            P.includes(a) && P.splice(P.indexOf(a), 1)),
        I &&
            P.forEach((M) => {
                ;((f[M].swiperLoopMoveDOM = !0),
                    v.prepend(f[M]),
                    (f[M].swiperLoopMoveDOM = !1))
            }),
        C &&
            b.forEach((M) => {
                ;((f[M].swiperLoopMoveDOM = !0),
                    v.append(f[M]),
                    (f[M].swiperLoopMoveDOM = !1))
            }),
        r.recalcSlides(),
        d.slidesPerView === 'auto'
            ? r.updateSlides()
            : L &&
              ((P.length > 0 && I) || (b.length > 0 && C)) &&
              r.slides.forEach((M, z) => {
                  r.grid.updateSlide(z, M, r.slides)
              }),
        d.watchSlidesProgress && r.updateSlidesOffset(),
        s)
    ) {
        if (P.length > 0 && I) {
            if (typeof e > 'u') {
                const M = r.slidesGrid[w],
                    G = r.slidesGrid[w + A] - M
                o
                    ? r.setTranslate(r.translate - G)
                    : (r.slideTo(w + Math.ceil(A), 0, !1, !0),
                      i &&
                          ((r.touchEventsData.startTranslate =
                              r.touchEventsData.startTranslate - G),
                          (r.touchEventsData.currentTranslate =
                              r.touchEventsData.currentTranslate - G)))
            } else if (i) {
                const M = L ? P.length / d.grid.rows : P.length
                ;(r.slideTo(r.activeIndex + M, 0, !1, !0),
                    (r.touchEventsData.currentTranslate = r.translate))
            }
        } else if (b.length > 0 && C)
            if (typeof e > 'u') {
                const M = r.slidesGrid[w],
                    G = r.slidesGrid[w - D] - M
                o
                    ? r.setTranslate(r.translate - G)
                    : (r.slideTo(w - D, 0, !1, !0),
                      i &&
                          ((r.touchEventsData.startTranslate =
                              r.touchEventsData.startTranslate - G),
                          (r.touchEventsData.currentTranslate =
                              r.touchEventsData.currentTranslate - G)))
            } else {
                const M = L ? b.length / d.grid.rows : b.length
                r.slideTo(r.activeIndex - M, 0, !1, !0)
            }
    }
    if (
        ((r.allowSlidePrev = g),
        (r.allowSlideNext = u),
        r.controller && r.controller.control && !l)
    ) {
        const M = {
            slideRealIndex: e,
            direction: t,
            setTranslate: i,
            activeSlideIndex: a,
            byController: !0,
        }
        Array.isArray(r.controller.control)
            ? r.controller.control.forEach((z) => {
                  !z.destroyed &&
                      z.params.loop &&
                      z.loopFix({
                          ...M,
                          slideTo:
                              z.params.slidesPerView === d.slidesPerView
                                  ? s
                                  : !1,
                      })
              })
            : r.controller.control instanceof r.constructor &&
              r.controller.control.params.loop &&
              r.controller.control.loopFix({
                  ...M,
                  slideTo:
                      r.controller.control.params.slidesPerView ===
                      d.slidesPerView
                          ? s
                          : !1,
              })
    }
    r.emit('loopFix')
}
function wt() {
    const e = this,
        { params: s, slidesEl: t } = e
    if (!s.loop || !t || (e.virtual && e.params.virtual.enabled)) return
    e.recalcSlides()
    const i = []
    ;(e.slides.forEach((a) => {
        const n =
            typeof a.swiperSlideIndex > 'u'
                ? a.getAttribute('data-swiper-slide-index') * 1
                : a.swiperSlideIndex
        i[n] = a
    }),
        e.slides.forEach((a) => {
            a.removeAttribute('data-swiper-slide-index')
        }),
        i.forEach((a) => {
            t.append(a)
        }),
        e.recalcSlides(),
        e.slideTo(e.realIndex, 0))
}
var Ct = { loopCreate: xt, loopFix: Et, loopDestroy: wt }
function Mt(e) {
    const s = this
    if (
        !s.params.simulateTouch ||
        (s.params.watchOverflow && s.isLocked) ||
        s.params.cssMode
    )
        return
    const t = s.params.touchEventsTarget === 'container' ? s.el : s.wrapperEl
    ;(s.isElement && (s.__preventObserver__ = !0),
        (t.style.cursor = 'move'),
        (t.style.cursor = e ? 'grabbing' : 'grab'),
        s.isElement &&
            requestAnimationFrame(() => {
                s.__preventObserver__ = !1
            }))
}
function Pt() {
    const e = this
    ;(e.params.watchOverflow && e.isLocked) ||
        e.params.cssMode ||
        (e.isElement && (e.__preventObserver__ = !0),
        (e[
            e.params.touchEventsTarget === 'container' ? 'el' : 'wrapperEl'
        ].style.cursor = ''),
        e.isElement &&
            requestAnimationFrame(() => {
                e.__preventObserver__ = !1
            }))
}
var Lt = { setGrabCursor: Mt, unsetGrabCursor: Pt }
function It(e, s = this) {
    function t(i) {
        if (!i || i === N() || i === V()) return null
        i.assignedSlot && (i = i.assignedSlot)
        const a = i.closest(e)
        return !a && !i.getRootNode ? null : a || t(i.getRootNode().host)
    }
    return t(s)
}
function ue(e, s, t) {
    const i = V(),
        { params: a } = e,
        n = a.edgeSwipeDetection,
        l = a.edgeSwipeThreshold
    return n && (t <= l || t >= i.innerWidth - l)
        ? n === 'prevent'
            ? (s.preventDefault(), !0)
            : !1
        : !0
}
function Ot(e) {
    const s = this,
        t = N()
    let i = e
    i.originalEvent && (i = i.originalEvent)
    const a = s.touchEventsData
    if (i.type === 'pointerdown') {
        if (a.pointerId !== null && a.pointerId !== i.pointerId) return
        a.pointerId = i.pointerId
    } else
        i.type === 'touchstart' &&
            i.targetTouches.length === 1 &&
            (a.touchId = i.targetTouches[0].identifier)
    if (i.type === 'touchstart') {
        ue(s, i, i.targetTouches[0].pageX)
        return
    }
    const { params: n, touches: l, enabled: o } = s
    if (
        !o ||
        (!n.simulateTouch && i.pointerType === 'mouse') ||
        (s.animating && n.preventInteractionOnTransition)
    )
        return
    !s.animating && n.cssMode && n.loop && s.loopFix()
    let r = i.target
    if (
        (n.touchEventsTarget === 'wrapper' && !Ve(r, s.wrapperEl)) ||
        ('which' in i && i.which === 3) ||
        ('button' in i && i.button > 0) ||
        (a.isTouched && a.isMoved)
    )
        return
    const f = !!n.noSwipingClass && n.noSwipingClass !== '',
        g = i.composedPath ? i.composedPath() : i.path
    f && i.target && i.target.shadowRoot && g && (r = g[0])
    const u = n.noSwipingSelector
            ? n.noSwipingSelector
            : `.${n.noSwipingClass}`,
        v = !!(i.target && i.target.shadowRoot)
    if (n.noSwiping && (v ? It(u, r) : r.closest(u))) {
        s.allowClick = !0
        return
    }
    if (n.swipeHandler && !r.closest(n.swipeHandler)) return
    ;((l.currentX = i.pageX), (l.currentY = i.pageY))
    const d = l.currentX,
        h = l.currentY
    if (!ue(s, i, d)) return
    ;(Object.assign(a, {
        isTouched: !0,
        isMoved: !1,
        allowTouchCallbacks: !0,
        isScrolling: void 0,
        startMoving: void 0,
    }),
        (l.startX = d),
        (l.startY = h),
        (a.touchStartTime = U()),
        (s.allowClick = !0),
        s.updateSize(),
        (s.swipeDirection = void 0),
        n.threshold > 0 && (a.allowThresholdMove = !1))
    let y = !0
    ;(r.matches(a.focusableElements) &&
        ((y = !1), r.nodeName === 'SELECT' && (a.isTouched = !1)),
        t.activeElement &&
            t.activeElement.matches(a.focusableElements) &&
            t.activeElement !== r &&
            (i.pointerType === 'mouse' ||
                (i.pointerType !== 'mouse' &&
                    !r.matches(a.focusableElements))) &&
            t.activeElement.blur())
    const x = y && s.allowTouchMove && n.touchStartPreventDefault
    ;((n.touchStartForcePreventDefault || x) &&
        !r.isContentEditable &&
        i.preventDefault(),
        n.freeMode &&
            n.freeMode.enabled &&
            s.freeMode &&
            s.animating &&
            !n.cssMode &&
            s.freeMode.onTouchStart(),
        s.emit('touchStart', i))
}
function kt(e) {
    const s = N(),
        t = this,
        i = t.touchEventsData,
        { params: a, touches: n, rtlTranslate: l, enabled: o } = t
    if (!o || (!a.simulateTouch && e.pointerType === 'mouse')) return
    let r = e
    if (
        (r.originalEvent && (r = r.originalEvent),
        r.type === 'pointermove' &&
            (i.touchId !== null || r.pointerId !== i.pointerId))
    )
        return
    let f
    if (r.type === 'touchmove') {
        if (
            ((f = [...r.changedTouches].find(
                (T) => T.identifier === i.touchId,
            )),
            !f || f.identifier !== i.touchId)
        )
            return
    } else f = r
    if (!i.isTouched) {
        i.startMoving && i.isScrolling && t.emit('touchMoveOpposite', r)
        return
    }
    const g = f.pageX,
        u = f.pageY
    if (r.preventedByNestedSwiper) {
        ;((n.startX = g), (n.startY = u))
        return
    }
    if (!t.allowTouchMove) {
        ;(r.target.matches(i.focusableElements) || (t.allowClick = !1),
            i.isTouched &&
                (Object.assign(n, {
                    startX: g,
                    startY: u,
                    currentX: g,
                    currentY: u,
                }),
                (i.touchStartTime = U())))
        return
    }
    if (a.touchReleaseOnEdges && !a.loop)
        if (t.isVertical()) {
            if (
                (u < n.startY && t.translate <= t.maxTranslate()) ||
                (u > n.startY && t.translate >= t.minTranslate())
            ) {
                ;((i.isTouched = !1), (i.isMoved = !1))
                return
            }
        } else {
            if (
                l &&
                ((g > n.startX && -t.translate <= t.maxTranslate()) ||
                    (g < n.startX && -t.translate >= t.minTranslate()))
            )
                return
            if (
                !l &&
                ((g < n.startX && t.translate <= t.maxTranslate()) ||
                    (g > n.startX && t.translate >= t.minTranslate()))
            )
                return
        }
    if (
        (s.activeElement &&
            s.activeElement.matches(i.focusableElements) &&
            s.activeElement !== r.target &&
            r.pointerType !== 'mouse' &&
            s.activeElement.blur(),
        s.activeElement &&
            r.target === s.activeElement &&
            r.target.matches(i.focusableElements))
    ) {
        ;((i.isMoved = !0), (t.allowClick = !1))
        return
    }
    ;(i.allowTouchCallbacks && t.emit('touchMove', r),
        (n.previousX = n.currentX),
        (n.previousY = n.currentY),
        (n.currentX = g),
        (n.currentY = u))
    const v = n.currentX - n.startX,
        d = n.currentY - n.startY
    if (t.params.threshold && Math.sqrt(v ** 2 + d ** 2) < t.params.threshold)
        return
    if (typeof i.isScrolling > 'u') {
        let T
        ;(t.isHorizontal() && n.currentY === n.startY) ||
        (t.isVertical() && n.currentX === n.startX)
            ? (i.isScrolling = !1)
            : v * v + d * d >= 25 &&
              ((T = (Math.atan2(Math.abs(d), Math.abs(v)) * 180) / Math.PI),
              (i.isScrolling = t.isHorizontal()
                  ? T > a.touchAngle
                  : 90 - T > a.touchAngle))
    }
    if (
        (i.isScrolling && t.emit('touchMoveOpposite', r),
        typeof i.startMoving > 'u' &&
            (n.currentX !== n.startX || n.currentY !== n.startY) &&
            (i.startMoving = !0),
        i.isScrolling ||
            (r.type === 'touchmove' && i.preventTouchMoveFromPointerMove))
    ) {
        i.isTouched = !1
        return
    }
    if (!i.startMoving) return
    ;((t.allowClick = !1),
        !a.cssMode && r.cancelable && r.preventDefault(),
        a.touchMoveStopPropagation && !a.nested && r.stopPropagation())
    let h = t.isHorizontal() ? v : d,
        y = t.isHorizontal()
            ? n.currentX - n.previousX
            : n.currentY - n.previousY
    ;(a.oneWayMovement &&
        ((h = Math.abs(h) * (l ? 1 : -1)), (y = Math.abs(y) * (l ? 1 : -1))),
        (n.diff = h),
        (h *= a.touchRatio),
        l && ((h = -h), (y = -y)))
    const x = t.touchesDirection
    ;((t.swipeDirection = h > 0 ? 'prev' : 'next'),
        (t.touchesDirection = y > 0 ? 'prev' : 'next'))
    const c = t.params.loop && !a.cssMode,
        p =
            (t.touchesDirection === 'next' && t.allowSlideNext) ||
            (t.touchesDirection === 'prev' && t.allowSlidePrev)
    if (!i.isMoved) {
        if (
            (c && p && t.loopFix({ direction: t.swipeDirection }),
            (i.startTranslate = t.getTranslate()),
            t.setTransition(0),
            t.animating)
        ) {
            const T = new window.CustomEvent('transitionend', {
                bubbles: !0,
                cancelable: !0,
                detail: { bySwiperTouchMove: !0 },
            })
            t.wrapperEl.dispatchEvent(T)
        }
        ;((i.allowMomentumBounce = !1),
            a.grabCursor &&
                (t.allowSlideNext === !0 || t.allowSlidePrev === !0) &&
                t.setGrabCursor(!0),
            t.emit('sliderFirstMove', r))
    }
    if (
        (new Date().getTime(),
        a._loopSwapReset !== !1 &&
            i.isMoved &&
            i.allowThresholdMove &&
            x !== t.touchesDirection &&
            c &&
            p &&
            Math.abs(h) >= 1)
    ) {
        ;(Object.assign(n, {
            startX: g,
            startY: u,
            currentX: g,
            currentY: u,
            startTranslate: i.currentTranslate,
        }),
            (i.loopSwapReset = !0),
            (i.startTranslate = i.currentTranslate))
        return
    }
    ;(t.emit('sliderMove', r),
        (i.isMoved = !0),
        (i.currentTranslate = h + i.startTranslate))
    let m = !0,
        S = a.resistanceRatio
    if (
        (a.touchReleaseOnEdges && (S = 0),
        h > 0
            ? (c &&
                  p &&
                  i.allowThresholdMove &&
                  i.currentTranslate >
                      (a.centeredSlides
                          ? t.minTranslate() -
                            t.slidesSizesGrid[t.activeIndex + 1] -
                            (a.slidesPerView !== 'auto' &&
                            t.slides.length - a.slidesPerView >= 2
                                ? t.slidesSizesGrid[t.activeIndex + 1] +
                                  t.params.spaceBetween
                                : 0) -
                            t.params.spaceBetween
                          : t.minTranslate()) &&
                  t.loopFix({
                      direction: 'prev',
                      setTranslate: !0,
                      activeSlideIndex: 0,
                  }),
              i.currentTranslate > t.minTranslate() &&
                  ((m = !1),
                  a.resistance &&
                      (i.currentTranslate =
                          t.minTranslate() -
                          1 +
                          (-t.minTranslate() + i.startTranslate + h) ** S)))
            : h < 0 &&
              (c &&
                  p &&
                  i.allowThresholdMove &&
                  i.currentTranslate <
                      (a.centeredSlides
                          ? t.maxTranslate() +
                            t.slidesSizesGrid[t.slidesSizesGrid.length - 1] +
                            t.params.spaceBetween +
                            (a.slidesPerView !== 'auto' &&
                            t.slides.length - a.slidesPerView >= 2
                                ? t.slidesSizesGrid[
                                      t.slidesSizesGrid.length - 1
                                  ] + t.params.spaceBetween
                                : 0)
                          : t.maxTranslate()) &&
                  t.loopFix({
                      direction: 'next',
                      setTranslate: !0,
                      activeSlideIndex:
                          t.slides.length -
                          (a.slidesPerView === 'auto'
                              ? t.slidesPerViewDynamic()
                              : Math.ceil(parseFloat(a.slidesPerView, 10))),
                  }),
              i.currentTranslate < t.maxTranslate() &&
                  ((m = !1),
                  a.resistance &&
                      (i.currentTranslate =
                          t.maxTranslate() +
                          1 -
                          (t.maxTranslate() - i.startTranslate - h) ** S))),
        m && (r.preventedByNestedSwiper = !0),
        !t.allowSlideNext &&
            t.swipeDirection === 'next' &&
            i.currentTranslate < i.startTranslate &&
            (i.currentTranslate = i.startTranslate),
        !t.allowSlidePrev &&
            t.swipeDirection === 'prev' &&
            i.currentTranslate > i.startTranslate &&
            (i.currentTranslate = i.startTranslate),
        !t.allowSlidePrev &&
            !t.allowSlideNext &&
            (i.currentTranslate = i.startTranslate),
        a.threshold > 0)
    )
        if (Math.abs(h) > a.threshold || i.allowThresholdMove) {
            if (!i.allowThresholdMove) {
                ;((i.allowThresholdMove = !0),
                    (n.startX = n.currentX),
                    (n.startY = n.currentY),
                    (i.currentTranslate = i.startTranslate),
                    (n.diff = t.isHorizontal()
                        ? n.currentX - n.startX
                        : n.currentY - n.startY))
                return
            }
        } else {
            i.currentTranslate = i.startTranslate
            return
        }
    !a.followFinger ||
        a.cssMode ||
        (((a.freeMode && a.freeMode.enabled && t.freeMode) ||
            a.watchSlidesProgress) &&
            (t.updateActiveIndex(), t.updateSlidesClasses()),
        a.freeMode &&
            a.freeMode.enabled &&
            t.freeMode &&
            t.freeMode.onTouchMove(),
        t.updateProgress(i.currentTranslate),
        t.setTranslate(i.currentTranslate))
}
function At(e) {
    const s = this,
        t = s.touchEventsData
    let i = e
    i.originalEvent && (i = i.originalEvent)
    let a
    if (i.type === 'touchend' || i.type === 'touchcancel') {
        if (
            ((a = [...i.changedTouches].find(
                (T) => T.identifier === t.touchId,
            )),
            !a || a.identifier !== t.touchId)
        )
            return
    } else {
        if (t.touchId !== null || i.pointerId !== t.pointerId) return
        a = i
    }
    if (
        ['pointercancel', 'pointerout', 'pointerleave', 'contextmenu'].includes(
            i.type,
        ) &&
        !(
            ['pointercancel', 'contextmenu'].includes(i.type) &&
            (s.browser.isSafari || s.browser.isWebView)
        )
    )
        return
    ;((t.pointerId = null), (t.touchId = null))
    const {
        params: l,
        touches: o,
        rtlTranslate: r,
        slidesGrid: f,
        enabled: g,
    } = s
    if (!g || (!l.simulateTouch && i.pointerType === 'mouse')) return
    if (
        (t.allowTouchCallbacks && s.emit('touchEnd', i),
        (t.allowTouchCallbacks = !1),
        !t.isTouched)
    ) {
        ;(t.isMoved && l.grabCursor && s.setGrabCursor(!1),
            (t.isMoved = !1),
            (t.startMoving = !1))
        return
    }
    l.grabCursor &&
        t.isMoved &&
        t.isTouched &&
        (s.allowSlideNext === !0 || s.allowSlidePrev === !0) &&
        s.setGrabCursor(!1)
    const u = U(),
        v = u - t.touchStartTime
    if (s.allowClick) {
        const T = i.path || (i.composedPath && i.composedPath())
        ;(s.updateClickedSlide((T && T[0]) || i.target, T),
            s.emit('tap click', i),
            v < 300 &&
                u - t.lastClickTime < 300 &&
                s.emit('doubleTap doubleClick', i))
    }
    if (
        ((t.lastClickTime = U()),
        ye(() => {
            s.destroyed || (s.allowClick = !0)
        }),
        !t.isTouched ||
            !t.isMoved ||
            !s.swipeDirection ||
            (o.diff === 0 && !t.loopSwapReset) ||
            (t.currentTranslate === t.startTranslate && !t.loopSwapReset))
    ) {
        ;((t.isTouched = !1), (t.isMoved = !1), (t.startMoving = !1))
        return
    }
    ;((t.isTouched = !1), (t.isMoved = !1), (t.startMoving = !1))
    let d
    if (
        (l.followFinger
            ? (d = r ? s.translate : -s.translate)
            : (d = -t.currentTranslate),
        l.cssMode)
    )
        return
    if (l.freeMode && l.freeMode.enabled) {
        s.freeMode.onTouchEnd({ currentPos: d })
        return
    }
    const h = d >= -s.maxTranslate() && !s.params.loop
    let y = 0,
        x = s.slidesSizesGrid[0]
    for (
        let T = 0;
        T < f.length;
        T += T < l.slidesPerGroupSkip ? 1 : l.slidesPerGroup
    ) {
        const L = T < l.slidesPerGroupSkip - 1 ? 1 : l.slidesPerGroup
        typeof f[T + L] < 'u'
            ? (h || (d >= f[T] && d < f[T + L])) &&
              ((y = T), (x = f[T + L] - f[T]))
            : (h || d >= f[T]) &&
              ((y = T), (x = f[f.length - 1] - f[f.length - 2]))
    }
    let c = null,
        p = null
    l.rewind &&
        (s.isBeginning
            ? (p =
                  l.virtual && l.virtual.enabled && s.virtual
                      ? s.virtual.slides.length - 1
                      : s.slides.length - 1)
            : s.isEnd && (c = 0))
    const m = (d - f[y]) / x,
        S = y < l.slidesPerGroupSkip - 1 ? 1 : l.slidesPerGroup
    if (v > l.longSwipesMs) {
        if (!l.longSwipes) {
            s.slideTo(s.activeIndex)
            return
        }
        ;(s.swipeDirection === 'next' &&
            (m >= l.longSwipesRatio
                ? s.slideTo(l.rewind && s.isEnd ? c : y + S)
                : s.slideTo(y)),
            s.swipeDirection === 'prev' &&
                (m > 1 - l.longSwipesRatio
                    ? s.slideTo(y + S)
                    : p !== null && m < 0 && Math.abs(m) > l.longSwipesRatio
                      ? s.slideTo(p)
                      : s.slideTo(y)))
    } else {
        if (!l.shortSwipes) {
            s.slideTo(s.activeIndex)
            return
        }
        s.navigation &&
        (i.target === s.navigation.nextEl || i.target === s.navigation.prevEl)
            ? i.target === s.navigation.nextEl
                ? s.slideTo(y + S)
                : s.slideTo(y)
            : (s.swipeDirection === 'next' && s.slideTo(c !== null ? c : y + S),
              s.swipeDirection === 'prev' && s.slideTo(p !== null ? p : y))
    }
}
function pe() {
    const e = this,
        { params: s, el: t } = e
    if (t && t.offsetWidth === 0) return
    s.breakpoints && e.setBreakpoint()
    const { allowSlideNext: i, allowSlidePrev: a, snapGrid: n } = e,
        l = e.virtual && e.params.virtual.enabled
    ;((e.allowSlideNext = !0),
        (e.allowSlidePrev = !0),
        e.updateSize(),
        e.updateSlides(),
        e.updateSlidesClasses())
    const o = l && s.loop
    if (
        (s.slidesPerView === 'auto' || s.slidesPerView > 1) &&
        e.isEnd &&
        !e.isBeginning &&
        !e.params.centeredSlides &&
        !o
    ) {
        const r = l ? e.virtual.slides : e.slides
        e.slideTo(r.length - 1, 0, !1, !0)
    } else
        e.params.loop && !l
            ? e.slideToLoop(e.realIndex, 0, !1, !0)
            : e.slideTo(e.activeIndex, 0, !1, !0)
    ;(e.autoplay &&
        e.autoplay.running &&
        e.autoplay.paused &&
        (clearTimeout(e.autoplay.resizeTimeout),
        (e.autoplay.resizeTimeout = setTimeout(() => {
            e.autoplay &&
                e.autoplay.running &&
                e.autoplay.paused &&
                e.autoplay.resume()
        }, 500))),
        (e.allowSlidePrev = a),
        (e.allowSlideNext = i),
        e.params.watchOverflow && n !== e.snapGrid && e.checkOverflow())
}
function zt(e) {
    const s = this
    s.enabled &&
        (s.allowClick ||
            (s.params.preventClicks && e.preventDefault(),
            s.params.preventClicksPropagation &&
                s.animating &&
                (e.stopPropagation(), e.stopImmediatePropagation())))
}
function Dt() {
    const e = this,
        { wrapperEl: s, rtlTranslate: t, enabled: i } = e
    if (!i) return
    ;((e.previousTranslate = e.translate),
        e.isHorizontal()
            ? (e.translate = -s.scrollLeft)
            : (e.translate = -s.scrollTop),
        e.translate === 0 && (e.translate = 0),
        e.updateActiveIndex(),
        e.updateSlidesClasses())
    let a
    const n = e.maxTranslate() - e.minTranslate()
    ;(n === 0 ? (a = 0) : (a = (e.translate - e.minTranslate()) / n),
        a !== e.progress && e.updateProgress(t ? -e.translate : e.translate),
        e.emit('setTranslate', e.translate, !1))
}
function Gt(e) {
    const s = this
    ;(X(s, e.target),
        !(
            s.params.cssMode ||
            (s.params.slidesPerView !== 'auto' && !s.params.autoHeight)
        ) && s.update())
}
function Bt() {
    const e = this
    e.documentTouchHandlerProceeded ||
        ((e.documentTouchHandlerProceeded = !0),
        e.params.touchReleaseOnEdges && (e.el.style.touchAction = 'auto'))
}
const Me = (e, s) => {
    const t = N(),
        { params: i, el: a, wrapperEl: n, device: l } = e,
        o = !!i.nested,
        r = s === 'on' ? 'addEventListener' : 'removeEventListener',
        f = s
    !a ||
        typeof a == 'string' ||
        (t[r]('touchstart', e.onDocumentTouchStart, {
            passive: !1,
            capture: o,
        }),
        a[r]('touchstart', e.onTouchStart, { passive: !1 }),
        a[r]('pointerdown', e.onTouchStart, { passive: !1 }),
        t[r]('touchmove', e.onTouchMove, { passive: !1, capture: o }),
        t[r]('pointermove', e.onTouchMove, { passive: !1, capture: o }),
        t[r]('touchend', e.onTouchEnd, { passive: !0 }),
        t[r]('pointerup', e.onTouchEnd, { passive: !0 }),
        t[r]('pointercancel', e.onTouchEnd, { passive: !0 }),
        t[r]('touchcancel', e.onTouchEnd, { passive: !0 }),
        t[r]('pointerout', e.onTouchEnd, { passive: !0 }),
        t[r]('pointerleave', e.onTouchEnd, { passive: !0 }),
        t[r]('contextmenu', e.onTouchEnd, { passive: !0 }),
        (i.preventClicks || i.preventClicksPropagation) &&
            a[r]('click', e.onClick, !0),
        i.cssMode && n[r]('scroll', e.onScroll),
        i.updateOnWindowResize
            ? e[f](
                  l.ios || l.android
                      ? 'resize orientationchange observerUpdate'
                      : 'resize observerUpdate',
                  pe,
                  !0,
              )
            : e[f]('observerUpdate', pe, !0),
        a[r]('load', e.onLoad, { capture: !0 }))
}
function Vt() {
    const e = this,
        { params: s } = e
    ;((e.onTouchStart = Ot.bind(e)),
        (e.onTouchMove = kt.bind(e)),
        (e.onTouchEnd = At.bind(e)),
        (e.onDocumentTouchStart = Bt.bind(e)),
        s.cssMode && (e.onScroll = Dt.bind(e)),
        (e.onClick = zt.bind(e)),
        (e.onLoad = Gt.bind(e)),
        Me(e, 'on'))
}
function $t() {
    Me(this, 'off')
}
var Ft = { attachEvents: Vt, detachEvents: $t }
const me = (e, s) => e.grid && s.grid && s.grid.rows > 1
function _t() {
    const e = this,
        { realIndex: s, initialized: t, params: i, el: a } = e,
        n = i.breakpoints
    if (!n || (n && Object.keys(n).length === 0)) return
    const l = N(),
        o =
            i.breakpointsBase === 'window' || !i.breakpointsBase
                ? i.breakpointsBase
                : 'container',
        r =
            ['window', 'container'].includes(i.breakpointsBase) ||
            !i.breakpointsBase
                ? e.el
                : l.querySelector(i.breakpointsBase),
        f = e.getBreakpoint(n, o, r)
    if (!f || e.currentBreakpoint === f) return
    const u = (f in n ? n[f] : void 0) || e.originalParams,
        v = me(e, i),
        d = me(e, u),
        h = e.params.grabCursor,
        y = u.grabCursor,
        x = i.enabled
    ;(v && !d
        ? (a.classList.remove(
              `${i.containerModifierClass}grid`,
              `${i.containerModifierClass}grid-column`,
          ),
          e.emitContainerClasses())
        : !v &&
          d &&
          (a.classList.add(`${i.containerModifierClass}grid`),
          ((u.grid.fill && u.grid.fill === 'column') ||
              (!u.grid.fill && i.grid.fill === 'column')) &&
              a.classList.add(`${i.containerModifierClass}grid-column`),
          e.emitContainerClasses()),
        h && !y ? e.unsetGrabCursor() : !h && y && e.setGrabCursor(),
        ['navigation', 'pagination', 'scrollbar'].forEach((L) => {
            if (typeof u[L] > 'u') return
            const P = i[L] && i[L].enabled,
                b = u[L] && u[L].enabled
            ;(P && !b && e[L].disable(), !P && b && e[L].enable())
        }))
    const c = u.direction && u.direction !== i.direction,
        p = i.loop && (u.slidesPerView !== i.slidesPerView || c),
        m = i.loop
    ;(c && t && e.changeDirection(), $(e.params, u))
    const S = e.params.enabled,
        T = e.params.loop
    ;(Object.assign(e, {
        allowTouchMove: e.params.allowTouchMove,
        allowSlideNext: e.params.allowSlideNext,
        allowSlidePrev: e.params.allowSlidePrev,
    }),
        x && !S ? e.disable() : !x && S && e.enable(),
        (e.currentBreakpoint = f),
        e.emit('_beforeBreakpoint', u),
        t &&
            (p
                ? (e.loopDestroy(), e.loopCreate(s), e.updateSlides())
                : !m && T
                  ? (e.loopCreate(s), e.updateSlides())
                  : m && !T && e.loopDestroy()),
        e.emit('breakpoint', u))
}
function Ht(e, s = 'window', t) {
    if (!e || (s === 'container' && !t)) return
    let i = !1
    const a = V(),
        n = s === 'window' ? a.innerHeight : t.clientHeight,
        l = Object.keys(e).map((o) => {
            if (typeof o == 'string' && o.indexOf('@') === 0) {
                const r = parseFloat(o.substr(1))
                return { value: n * r, point: o }
            }
            return { value: o, point: o }
        })
    l.sort((o, r) => parseInt(o.value, 10) - parseInt(r.value, 10))
    for (let o = 0; o < l.length; o += 1) {
        const { point: r, value: f } = l[o]
        s === 'window'
            ? a.matchMedia(`(min-width: ${f}px)`).matches && (i = r)
            : f <= t.clientWidth && (i = r)
    }
    return i || 'max'
}
var Nt = { setBreakpoint: _t, getBreakpoint: Ht }
function Rt(e, s) {
    const t = []
    return (
        e.forEach((i) => {
            typeof i == 'object'
                ? Object.keys(i).forEach((a) => {
                      i[a] && t.push(s + a)
                  })
                : typeof i == 'string' && t.push(s + i)
        }),
        t
    )
}
function qt() {
    const e = this,
        { classNames: s, params: t, rtl: i, el: a, device: n } = e,
        l = Rt(
            [
                'initialized',
                t.direction,
                { 'free-mode': e.params.freeMode && t.freeMode.enabled },
                { autoheight: t.autoHeight },
                { rtl: i },
                { grid: t.grid && t.grid.rows > 1 },
                {
                    'grid-column':
                        t.grid && t.grid.rows > 1 && t.grid.fill === 'column',
                },
                { android: n.android },
                { ios: n.ios },
                { 'css-mode': t.cssMode },
                { centered: t.cssMode && t.centeredSlides },
                { 'watch-progress': t.watchSlidesProgress },
            ],
            t.containerModifierClass,
        )
    ;(s.push(...l), a.classList.add(...s), e.emitContainerClasses())
}
function Wt() {
    const e = this,
        { el: s, classNames: t } = e
    !s ||
        typeof s == 'string' ||
        (s.classList.remove(...t), e.emitContainerClasses())
}
var jt = { addClasses: qt, removeClasses: Wt }
function Yt() {
    const e = this,
        { isLocked: s, params: t } = e,
        { slidesOffsetBefore: i } = t
    if (i) {
        const a = e.slides.length - 1,
            n = e.slidesGrid[a] + e.slidesSizesGrid[a] + i * 2
        e.isLocked = e.size > n
    } else e.isLocked = e.snapGrid.length === 1
    ;(t.allowSlideNext === !0 && (e.allowSlideNext = !e.isLocked),
        t.allowSlidePrev === !0 && (e.allowSlidePrev = !e.isLocked),
        s && s !== e.isLocked && (e.isEnd = !1),
        s !== e.isLocked && e.emit(e.isLocked ? 'lock' : 'unlock'))
}
var Xt = { checkOverflow: Yt },
    he = {
        init: !0,
        direction: 'horizontal',
        oneWayMovement: !1,
        swiperElementNodeName: 'SWIPER-CONTAINER',
        touchEventsTarget: 'wrapper',
        initialSlide: 0,
        speed: 300,
        cssMode: !1,
        updateOnWindowResize: !0,
        resizeObserver: !0,
        nested: !1,
        createElements: !1,
        eventsPrefix: 'swiper',
        enabled: !0,
        focusableElements:
            'input, select, option, textarea, button, video, label',
        width: null,
        height: null,
        preventInteractionOnTransition: !1,
        userAgent: null,
        url: null,
        edgeSwipeDetection: !1,
        edgeSwipeThreshold: 20,
        autoHeight: !1,
        setWrapperSize: !1,
        virtualTranslate: !1,
        effect: 'slide',
        breakpoints: void 0,
        breakpointsBase: 'window',
        spaceBetween: 0,
        slidesPerView: 1,
        slidesPerGroup: 1,
        slidesPerGroupSkip: 0,
        slidesPerGroupAuto: !1,
        centeredSlides: !1,
        centeredSlidesBounds: !1,
        slidesOffsetBefore: 0,
        slidesOffsetAfter: 0,
        normalizeSlideIndex: !0,
        centerInsufficientSlides: !1,
        snapToSlideEdge: !1,
        watchOverflow: !0,
        roundLengths: !1,
        touchRatio: 1,
        touchAngle: 45,
        simulateTouch: !0,
        shortSwipes: !0,
        longSwipes: !0,
        longSwipesRatio: 0.5,
        longSwipesMs: 300,
        followFinger: !0,
        allowTouchMove: !0,
        threshold: 5,
        touchMoveStopPropagation: !1,
        touchStartPreventDefault: !0,
        touchStartForcePreventDefault: !1,
        touchReleaseOnEdges: !1,
        uniqueNavElements: !0,
        resistance: !0,
        resistanceRatio: 0.85,
        watchSlidesProgress: !1,
        grabCursor: !1,
        preventClicks: !0,
        preventClicksPropagation: !0,
        slideToClickedSlide: !1,
        loop: !1,
        loopAddBlankSlides: !0,
        loopAdditionalSlides: 0,
        loopPreventsSliding: !0,
        rewind: !1,
        allowSlidePrev: !0,
        allowSlideNext: !0,
        swipeHandler: null,
        noSwiping: !0,
        noSwipingClass: 'swiper-no-swiping',
        noSwipingSelector: null,
        passiveListeners: !0,
        maxBackfaceHiddenSlides: 10,
        containerModifierClass: 'swiper-',
        slideClass: 'swiper-slide',
        slideBlankClass: 'swiper-slide-blank',
        slideActiveClass: 'swiper-slide-active',
        slideVisibleClass: 'swiper-slide-visible',
        slideFullyVisibleClass: 'swiper-slide-fully-visible',
        slideNextClass: 'swiper-slide-next',
        slidePrevClass: 'swiper-slide-prev',
        wrapperClass: 'swiper-wrapper',
        lazyPreloaderClass: 'swiper-lazy-preloader',
        lazyPreloadPrevNext: 0,
        runCallbacksOnInit: !0,
        _emitClasses: !1,
    }
function Ut(e, s) {
    return function (i = {}) {
        const a = Object.keys(i)[0],
            n = i[a]
        if (typeof n != 'object' || n === null) {
            $(s, i)
            return
        }
        if (
            (e[a] === !0 && (e[a] = { enabled: !0 }),
            a === 'navigation' &&
                e[a] &&
                e[a].enabled &&
                !e[a].prevEl &&
                !e[a].nextEl &&
                (e[a].auto = !0),
            ['pagination', 'scrollbar'].indexOf(a) >= 0 &&
                e[a] &&
                e[a].enabled &&
                !e[a].el &&
                (e[a].auto = !0),
            !(a in e && 'enabled' in n))
        ) {
            $(s, i)
            return
        }
        ;(typeof e[a] == 'object' &&
            !('enabled' in e[a]) &&
            (e[a].enabled = !0),
            e[a] || (e[a] = { enabled: !1 }),
            $(s, i))
    }
}
const ne = {
        eventsEmitter: je,
        update: it,
        translate: dt,
        transition: pt,
        slide: Tt,
        loop: Ct,
        grabCursor: Lt,
        events: Ft,
        breakpoints: Nt,
        checkOverflow: Xt,
        classes: jt,
    },
    ae = {}
class F {
    constructor(...s) {
        let t, i
        ;(s.length === 1 &&
        s[0].constructor &&
        Object.prototype.toString.call(s[0]).slice(8, -1) === 'Object'
            ? (i = s[0])
            : ([t, i] = s),
            i || (i = {}),
            (i = $({}, i)),
            t && !i.el && (i.el = t))
        const a = N()
        if (
            i.el &&
            typeof i.el == 'string' &&
            a.querySelectorAll(i.el).length > 1
        ) {
            const r = []
            return (
                a.querySelectorAll(i.el).forEach((f) => {
                    const g = $({}, i, { el: f })
                    r.push(new F(g))
                }),
                r
            )
        }
        const n = this
        ;((n.__swiper__ = !0),
            (n.support = xe()),
            (n.device = Ee({ userAgent: i.userAgent })),
            (n.browser = we()),
            (n.eventsListeners = {}),
            (n.eventsAnyListeners = []),
            (n.modules = [...n.__modules__]),
            i.modules &&
                Array.isArray(i.modules) &&
                i.modules.forEach((r) => {
                    typeof r == 'function' &&
                        n.modules.indexOf(r) < 0 &&
                        n.modules.push(r)
                }))
        const l = {}
        n.modules.forEach((r) => {
            r({
                params: i,
                swiper: n,
                extendParams: Ut(i, l),
                on: n.on.bind(n),
                once: n.once.bind(n),
                off: n.off.bind(n),
                emit: n.emit.bind(n),
            })
        })
        const o = $({}, he, l)
        return (
            (n.params = $({}, o, ae, i)),
            (n.originalParams = $({}, n.params)),
            (n.passedParams = $({}, i)),
            n.params &&
                n.params.on &&
                Object.keys(n.params.on).forEach((r) => {
                    n.on(r, n.params.on[r])
                }),
            n.params && n.params.onAny && n.onAny(n.params.onAny),
            Object.assign(n, {
                enabled: n.params.enabled,
                el: t,
                classNames: [],
                slides: [],
                slidesGrid: [],
                snapGrid: [],
                slidesSizesGrid: [],
                isHorizontal() {
                    return n.params.direction === 'horizontal'
                },
                isVertical() {
                    return n.params.direction === 'vertical'
                },
                activeIndex: 0,
                realIndex: 0,
                isBeginning: !0,
                isEnd: !1,
                translate: 0,
                previousTranslate: 0,
                progress: 0,
                velocity: 0,
                animating: !1,
                cssOverflowAdjustment() {
                    return Math.trunc(this.translate / 2 ** 23) * 2 ** 23
                },
                allowSlideNext: n.params.allowSlideNext,
                allowSlidePrev: n.params.allowSlidePrev,
                touchEventsData: {
                    isTouched: void 0,
                    isMoved: void 0,
                    allowTouchCallbacks: void 0,
                    touchStartTime: void 0,
                    isScrolling: void 0,
                    currentTranslate: void 0,
                    startTranslate: void 0,
                    allowThresholdMove: void 0,
                    focusableElements: n.params.focusableElements,
                    lastClickTime: 0,
                    clickTimeout: void 0,
                    velocities: [],
                    allowMomentumBounce: void 0,
                    startMoving: void 0,
                    pointerId: null,
                    touchId: null,
                },
                allowClick: !0,
                allowTouchMove: n.params.allowTouchMove,
                touches: {
                    startX: 0,
                    startY: 0,
                    currentX: 0,
                    currentY: 0,
                    diff: 0,
                },
                imagesToLoad: [],
                imagesLoaded: 0,
            }),
            n.emit('_swiper'),
            n.params.init && n.init(),
            n
        )
    }
    getDirectionLabel(s) {
        return this.isHorizontal()
            ? s
            : {
                  width: 'height',
                  'margin-top': 'margin-left',
                  'margin-bottom ': 'margin-right',
                  'margin-left': 'margin-top',
                  'margin-right': 'margin-bottom',
                  'padding-left': 'padding-top',
                  'padding-right': 'padding-bottom',
                  marginRight: 'marginBottom',
              }[s]
    }
    getSlideIndex(s) {
        const { slidesEl: t, params: i } = this,
            a = H(t, `.${i.slideClass}, swiper-slide`),
            n = Q(a[0])
        return Q(s) - n
    }
    getSlideIndexByData(s) {
        return this.getSlideIndex(
            this.slides.find(
                (t) => t.getAttribute('data-swiper-slide-index') * 1 === s,
            ),
        )
    }
    getSlideIndexWhenGrid(s) {
        return (
            this.grid &&
                this.params.grid &&
                this.params.grid.rows > 1 &&
                (this.params.grid.fill === 'column'
                    ? (s = Math.floor(s / this.params.grid.rows))
                    : this.params.grid.fill === 'row' &&
                      (s =
                          s %
                          Math.ceil(
                              this.slides.length / this.params.grid.rows,
                          ))),
            s
        )
    }
    recalcSlides() {
        const s = this,
            { slidesEl: t, params: i } = s
        s.slides = H(t, `.${i.slideClass}, swiper-slide`)
    }
    enable() {
        const s = this
        s.enabled ||
            ((s.enabled = !0),
            s.params.grabCursor && s.setGrabCursor(),
            s.emit('enable'))
    }
    disable() {
        const s = this
        s.enabled &&
            ((s.enabled = !1),
            s.params.grabCursor && s.unsetGrabCursor(),
            s.emit('disable'))
    }
    setProgress(s, t) {
        const i = this
        s = Math.min(Math.max(s, 0), 1)
        const a = i.minTranslate(),
            l = (i.maxTranslate() - a) * s + a
        ;(i.translateTo(l, typeof t > 'u' ? 0 : t),
            i.updateActiveIndex(),
            i.updateSlidesClasses())
    }
    emitContainerClasses() {
        const s = this
        if (!s.params._emitClasses || !s.el) return
        const t = s.el.className
            .split(' ')
            .filter(
                (i) =>
                    i.indexOf('swiper') === 0 ||
                    i.indexOf(s.params.containerModifierClass) === 0,
            )
        s.emit('_containerClasses', t.join(' '))
    }
    getSlideClasses(s) {
        const t = this
        return t.destroyed
            ? ''
            : s.className
                  .split(' ')
                  .filter(
                      (i) =>
                          i.indexOf('swiper-slide') === 0 ||
                          i.indexOf(t.params.slideClass) === 0,
                  )
                  .join(' ')
    }
    emitSlidesClasses() {
        const s = this
        if (!s.params._emitClasses || !s.el) return
        const t = []
        ;(s.slides.forEach((i) => {
            const a = s.getSlideClasses(i)
            ;(t.push({ slideEl: i, classNames: a }),
                s.emit('_slideClass', i, a))
        }),
            s.emit('_slideClasses', t))
    }
    slidesPerViewDynamic(s = 'current', t = !1) {
        const i = this,
            {
                params: a,
                slides: n,
                slidesGrid: l,
                slidesSizesGrid: o,
                size: r,
                activeIndex: f,
            } = i
        let g = 1
        if (typeof a.slidesPerView == 'number') return a.slidesPerView
        if (a.centeredSlides) {
            let u = n[f] ? Math.ceil(n[f].swiperSlideSize) : 0,
                v
            for (let d = f + 1; d < n.length; d += 1)
                n[d] &&
                    !v &&
                    ((u += Math.ceil(n[d].swiperSlideSize)),
                    (g += 1),
                    u > r && (v = !0))
            for (let d = f - 1; d >= 0; d -= 1)
                n[d] &&
                    !v &&
                    ((u += n[d].swiperSlideSize), (g += 1), u > r && (v = !0))
        } else if (s === 'current')
            for (let u = f + 1; u < n.length; u += 1)
                (t ? l[u] + o[u] - l[f] < r : l[u] - l[f] < r) && (g += 1)
        else for (let u = f - 1; u >= 0; u -= 1) l[f] - l[u] < r && (g += 1)
        return g
    }
    update() {
        const s = this
        if (!s || s.destroyed) return
        const { snapGrid: t, params: i } = s
        ;(i.breakpoints && s.setBreakpoint(),
            [...s.el.querySelectorAll('[loading="lazy"]')].forEach((l) => {
                l.complete && X(s, l)
            }),
            s.updateSize(),
            s.updateSlides(),
            s.updateProgress(),
            s.updateSlidesClasses())
        function a() {
            const l = s.rtlTranslate ? s.translate * -1 : s.translate,
                o = Math.min(Math.max(l, s.maxTranslate()), s.minTranslate())
            ;(s.setTranslate(o), s.updateActiveIndex(), s.updateSlidesClasses())
        }
        let n
        if (i.freeMode && i.freeMode.enabled && !i.cssMode)
            (a(), i.autoHeight && s.updateAutoHeight())
        else {
            if (
                (i.slidesPerView === 'auto' || i.slidesPerView > 1) &&
                s.isEnd &&
                !i.centeredSlides
            ) {
                const l =
                    s.virtual && i.virtual.enabled ? s.virtual.slides : s.slides
                n = s.slideTo(l.length - 1, 0, !1, !0)
            } else n = s.slideTo(s.activeIndex, 0, !1, !0)
            n || a()
        }
        ;(i.watchOverflow && t !== s.snapGrid && s.checkOverflow(),
            s.emit('update'))
    }
    changeDirection(s, t = !0) {
        const i = this,
            a = i.params.direction
        return (
            s || (s = a === 'horizontal' ? 'vertical' : 'horizontal'),
            s === a ||
                (s !== 'horizontal' && s !== 'vertical') ||
                (i.el.classList.remove(
                    `${i.params.containerModifierClass}${a}`,
                ),
                i.el.classList.add(`${i.params.containerModifierClass}${s}`),
                i.emitContainerClasses(),
                (i.params.direction = s),
                i.slides.forEach((n) => {
                    s === 'vertical'
                        ? (n.style.width = '')
                        : (n.style.height = '')
                }),
                i.emit('changeDirection'),
                t && i.update()),
            i
        )
    }
    changeLanguageDirection(s) {
        const t = this
        ;(t.rtl && s === 'rtl') ||
            (!t.rtl && s === 'ltr') ||
            ((t.rtl = s === 'rtl'),
            (t.rtlTranslate = t.params.direction === 'horizontal' && t.rtl),
            t.rtl
                ? (t.el.classList.add(`${t.params.containerModifierClass}rtl`),
                  (t.el.dir = 'rtl'))
                : (t.el.classList.remove(
                      `${t.params.containerModifierClass}rtl`,
                  ),
                  (t.el.dir = 'ltr')),
            t.update())
    }
    mount(s) {
        const t = this
        if (t.mounted) return !0
        let i = s || t.params.el
        if ((typeof i == 'string' && (i = document.querySelector(i)), !i))
            return !1
        ;((i.swiper = t),
            i.parentNode &&
                i.parentNode.host &&
                i.parentNode.host.nodeName ===
                    t.params.swiperElementNodeName.toUpperCase() &&
                (t.isElement = !0))
        const a = () =>
            `.${(t.params.wrapperClass || '').trim().split(' ').join('.')}`
        let l =
            i && i.shadowRoot && i.shadowRoot.querySelector
                ? i.shadowRoot.querySelector(a())
                : H(i, a())[0]
        return (
            !l &&
                t.params.createElements &&
                ((l = J('div', t.params.wrapperClass)),
                i.append(l),
                H(i, `.${t.params.slideClass}`).forEach((o) => {
                    l.append(o)
                })),
            Object.assign(t, {
                el: i,
                wrapperEl: l,
                slidesEl:
                    t.isElement && !i.parentNode.host.slideSlots
                        ? i.parentNode.host
                        : l,
                hostEl: t.isElement ? i.parentNode.host : i,
                mounted: !0,
                rtl:
                    i.dir.toLowerCase() === 'rtl' ||
                    q(i, 'direction') === 'rtl',
                rtlTranslate:
                    t.params.direction === 'horizontal' &&
                    (i.dir.toLowerCase() === 'rtl' ||
                        q(i, 'direction') === 'rtl'),
                wrongRTL: q(l, 'display') === '-webkit-box',
            }),
            !0
        )
    }
    init(s) {
        const t = this
        if (t.initialized || t.mount(s) === !1) return t
        ;(t.emit('beforeInit'),
            t.params.breakpoints && t.setBreakpoint(),
            t.addClasses(),
            t.updateSize(),
            t.updateSlides(),
            t.params.watchOverflow && t.checkOverflow(),
            t.params.grabCursor && t.enabled && t.setGrabCursor(),
            t.params.loop && t.virtual && t.params.virtual.enabled
                ? t.slideTo(
                      t.params.initialSlide + t.virtual.slidesBefore,
                      0,
                      t.params.runCallbacksOnInit,
                      !1,
                      !0,
                  )
                : t.slideTo(
                      t.params.initialSlide,
                      0,
                      t.params.runCallbacksOnInit,
                      !1,
                      !0,
                  ),
            t.params.loop && t.loopCreate(void 0, !0),
            t.attachEvents())
        const a = [...t.el.querySelectorAll('[loading="lazy"]')]
        return (
            t.isElement &&
                a.push(...t.hostEl.querySelectorAll('[loading="lazy"]')),
            a.forEach((n) => {
                n.complete
                    ? X(t, n)
                    : n.addEventListener('load', (l) => {
                          X(t, l.target)
                      })
            }),
            oe(t),
            (t.initialized = !0),
            oe(t),
            t.emit('init'),
            t.emit('afterInit'),
            t
        )
    }
    destroy(s = !0, t = !0) {
        const i = this,
            { params: a, el: n, wrapperEl: l, slides: o } = i
        return (
            typeof i.params > 'u' ||
                i.destroyed ||
                (i.emit('beforeDestroy'),
                (i.initialized = !1),
                i.detachEvents(),
                a.loop && i.loopDestroy(),
                t &&
                    (i.removeClasses(),
                    n && typeof n != 'string' && n.removeAttribute('style'),
                    l && l.removeAttribute('style'),
                    o &&
                        o.length &&
                        o.forEach((r) => {
                            ;(r.classList.remove(
                                a.slideVisibleClass,
                                a.slideFullyVisibleClass,
                                a.slideActiveClass,
                                a.slideNextClass,
                                a.slidePrevClass,
                            ),
                                r.removeAttribute('style'),
                                r.removeAttribute('data-swiper-slide-index'))
                        })),
                i.emit('destroy'),
                Object.keys(i.eventsListeners).forEach((r) => {
                    i.off(r)
                }),
                s !== !1 &&
                    (i.el && typeof i.el != 'string' && (i.el.swiper = null),
                    Ae(i)),
                (i.destroyed = !0)),
            null
        )
    }
    static extendDefaults(s) {
        $(ae, s)
    }
    static get extendedDefaults() {
        return ae
    }
    static get defaults() {
        return he
    }
    static installModule(s) {
        F.prototype.__modules__ || (F.prototype.__modules__ = [])
        const t = F.prototype.__modules__
        typeof s == 'function' && t.indexOf(s) < 0 && t.push(s)
    }
    static use(s) {
        return Array.isArray(s)
            ? (s.forEach((t) => F.installModule(t)), F)
            : (F.installModule(s), F)
    }
}
Object.keys(ne).forEach((e) => {
    Object.keys(ne[e]).forEach((s) => {
        F.prototype[s] = ne[e][s]
    })
})
F.use([qe, We])
function Pe(e, s, t, i) {
    return (
        e.params.createElements &&
            Object.keys(i).forEach((a) => {
                if (!t[a] && t.auto === !0) {
                    let n = H(e.el, `.${i[a]}`)[0]
                    ;(n ||
                        ((n = J('div', i[a])),
                        (n.className = i[a]),
                        e.el.append(n)),
                        (t[a] = n),
                        (s[a] = n))
                }
            }),
        t
    )
}
const ge =
    '<svg class="swiper-navigation-icon" width="11" height="20" viewBox="0 0 11 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.38296 20.0762C0.111788 19.805 0.111788 19.3654 0.38296 19.0942L9.19758 10.2796L0.38296 1.46497C0.111788 1.19379 0.111788 0.754138 0.38296 0.482966C0.654131 0.211794 1.09379 0.211794 1.36496 0.482966L10.4341 9.55214C10.8359 9.9539 10.8359 10.6053 10.4341 11.007L1.36496 20.0762C1.09379 20.3474 0.654131 20.3474 0.38296 20.0762Z" fill="currentColor"/></svg>'
function Kt({ swiper: e, extendParams: s, on: t, emit: i }) {
    ;(s({
        navigation: {
            nextEl: null,
            prevEl: null,
            addIcons: !0,
            hideOnClick: !1,
            disabledClass: 'swiper-button-disabled',
            hiddenClass: 'swiper-button-hidden',
            lockClass: 'swiper-button-lock',
            navigationDisabledClass: 'swiper-navigation-disabled',
        },
    }),
        (e.navigation = { nextEl: null, prevEl: null, arrowSvg: ge }))
    function a(d) {
        let h
        return d &&
            typeof d == 'string' &&
            e.isElement &&
            ((h = e.el.querySelector(d) || e.hostEl.querySelector(d)), h)
            ? h
            : (d &&
                  (typeof d == 'string' &&
                      (h = [...document.querySelectorAll(d)]),
                  e.params.uniqueNavElements &&
                  typeof d == 'string' &&
                  h &&
                  h.length > 1 &&
                  e.el.querySelectorAll(d).length === 1
                      ? (h = e.el.querySelector(d))
                      : h && h.length === 1 && (h = h[0])),
              d && !h ? d : h)
    }
    function n(d, h) {
        const y = e.params.navigation
        ;((d = B(d)),
            d.forEach((x) => {
                x &&
                    (x.classList[h ? 'add' : 'remove'](
                        ...y.disabledClass.split(' '),
                    ),
                    x.tagName === 'BUTTON' && (x.disabled = h),
                    e.params.watchOverflow &&
                        e.enabled &&
                        x.classList[e.isLocked ? 'add' : 'remove'](y.lockClass))
            }))
    }
    function l() {
        const { nextEl: d, prevEl: h } = e.navigation
        if (e.params.loop) {
            ;(n(h, !1), n(d, !1))
            return
        }
        ;(n(h, e.isBeginning && !e.params.rewind),
            n(d, e.isEnd && !e.params.rewind))
    }
    function o(d) {
        ;(d.preventDefault(),
            !(e.isBeginning && !e.params.loop && !e.params.rewind) &&
                (e.slidePrev(), i('navigationPrev')))
    }
    function r(d) {
        ;(d.preventDefault(),
            !(e.isEnd && !e.params.loop && !e.params.rewind) &&
                (e.slideNext(), i('navigationNext')))
    }
    function f() {
        const d = e.params.navigation
        if (
            ((e.params.navigation = Pe(
                e,
                e.originalParams.navigation,
                e.params.navigation,
                { nextEl: 'swiper-button-next', prevEl: 'swiper-button-prev' },
            )),
            !(d.nextEl || d.prevEl))
        )
            return
        let h = a(d.nextEl),
            y = a(d.prevEl)
        ;(Object.assign(e.navigation, { nextEl: h, prevEl: y }),
            (h = B(h)),
            (y = B(y)))
        const x = (c, p) => {
            if (c) {
                if (
                    d.addIcons &&
                    c.matches('.swiper-button-next,.swiper-button-prev') &&
                    !c.querySelector('svg')
                ) {
                    const m = document.createElement('div')
                    ;(le(m, ge),
                        c.appendChild(m.querySelector('svg')),
                        m.remove())
                }
                c.addEventListener('click', p === 'next' ? r : o)
            }
            !e.enabled && c && c.classList.add(...d.lockClass.split(' '))
        }
        ;(h.forEach((c) => x(c, 'next')), y.forEach((c) => x(c, 'prev')))
    }
    function g() {
        let { nextEl: d, prevEl: h } = e.navigation
        ;((d = B(d)), (h = B(h)))
        const y = (x, c) => {
            ;(x.removeEventListener('click', c === 'next' ? r : o),
                x.classList.remove(
                    ...e.params.navigation.disabledClass.split(' '),
                ))
        }
        ;(d.forEach((x) => y(x, 'next')), h.forEach((x) => y(x, 'prev')))
    }
    ;(t('init', () => {
        e.params.navigation.enabled === !1 ? v() : (f(), l())
    }),
        t('toEdge fromEdge lock unlock', () => {
            l()
        }),
        t('destroy', () => {
            g()
        }),
        t('enable disable', () => {
            let { nextEl: d, prevEl: h } = e.navigation
            if (((d = B(d)), (h = B(h)), e.enabled)) {
                l()
                return
            }
            ;[...d, ...h]
                .filter((y) => !!y)
                .forEach((y) => y.classList.add(e.params.navigation.lockClass))
        }),
        t('click', (d, h) => {
            let { nextEl: y, prevEl: x } = e.navigation
            ;((y = B(y)), (x = B(x)))
            const c = h.target
            let p = x.includes(c) || y.includes(c)
            if (e.isElement && !p) {
                const m = h.path || (h.composedPath && h.composedPath())
                m && (p = m.find((S) => y.includes(S) || x.includes(S)))
            }
            if (e.params.navigation.hideOnClick && !p) {
                if (
                    e.pagination &&
                    e.params.pagination &&
                    e.params.pagination.clickable &&
                    (e.pagination.el === c || e.pagination.el.contains(c))
                )
                    return
                let m
                ;(y.length
                    ? (m = y[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      ))
                    : x.length &&
                      (m = x[0].classList.contains(
                          e.params.navigation.hiddenClass,
                      )),
                    i(m === !0 ? 'navigationShow' : 'navigationHide'),
                    [...y, ...x]
                        .filter((S) => !!S)
                        .forEach((S) =>
                            S.classList.toggle(e.params.navigation.hiddenClass),
                        ))
            }
        }))
    const u = () => {
            ;(e.el.classList.remove(
                ...e.params.navigation.navigationDisabledClass.split(' '),
            ),
                f(),
                l())
        },
        v = () => {
            ;(e.el.classList.add(
                ...e.params.navigation.navigationDisabledClass.split(' '),
            ),
                g())
        }
    Object.assign(e.navigation, {
        enable: u,
        disable: v,
        update: l,
        init: f,
        destroy: g,
    })
}
function j(e = '') {
    return `.${e
        .trim()
        .replace(/([\.:!+\/()[\]#>~*^$|=,'"@{}\\])/g, '\\$1')
        .replace(/ /g, '.')}`
}
function Jt({ swiper: e, extendParams: s, on: t, emit: i }) {
    const a = 'swiper-pagination'
    ;(s({
        pagination: {
            el: null,
            bulletElement: 'span',
            clickable: !1,
            hideOnClick: !1,
            renderBullet: null,
            renderProgressbar: null,
            renderFraction: null,
            renderCustom: null,
            progressbarOpposite: !1,
            type: 'bullets',
            dynamicBullets: !1,
            dynamicMainBullets: 1,
            formatFractionCurrent: (c) => c,
            formatFractionTotal: (c) => c,
            bulletClass: `${a}-bullet`,
            bulletActiveClass: `${a}-bullet-active`,
            modifierClass: `${a}-`,
            currentClass: `${a}-current`,
            totalClass: `${a}-total`,
            hiddenClass: `${a}-hidden`,
            progressbarFillClass: `${a}-progressbar-fill`,
            progressbarOppositeClass: `${a}-progressbar-opposite`,
            clickableClass: `${a}-clickable`,
            lockClass: `${a}-lock`,
            horizontalClass: `${a}-horizontal`,
            verticalClass: `${a}-vertical`,
            paginationDisabledClass: `${a}-disabled`,
        },
    }),
        (e.pagination = { el: null, bullets: [] }))
    let n,
        l = 0
    function o() {
        return (
            !e.params.pagination.el ||
            !e.pagination.el ||
            (Array.isArray(e.pagination.el) && e.pagination.el.length === 0)
        )
    }
    function r(c, p) {
        const { bulletActiveClass: m } = e.params.pagination
        c &&
            ((c = c[`${p === 'prev' ? 'previous' : 'next'}ElementSibling`]),
            c &&
                (c.classList.add(`${m}-${p}`),
                (c = c[`${p === 'prev' ? 'previous' : 'next'}ElementSibling`]),
                c && c.classList.add(`${m}-${p}-${p}`)))
    }
    function f(c, p, m) {
        if (((c = c % m), (p = p % m), p === c + 1)) return 'next'
        if (p === c - 1) return 'previous'
    }
    function g(c) {
        const p = c.target.closest(j(e.params.pagination.bulletClass))
        if (!p) return
        c.preventDefault()
        const m = Q(p) * e.params.slidesPerGroup
        if (e.params.loop) {
            if (e.realIndex === m) return
            const S = f(e.realIndex, m, e.slides.length)
            S === 'next'
                ? e.slideNext()
                : S === 'previous'
                  ? e.slidePrev()
                  : e.slideToLoop(m)
        } else e.slideTo(m)
    }
    function u() {
        const c = e.rtl,
            p = e.params.pagination
        if (o()) return
        let m = e.pagination.el
        m = B(m)
        let S, T
        const L =
                e.virtual && e.params.virtual.enabled
                    ? e.virtual.slides.length
                    : e.slides.length,
            P = e.params.loop
                ? Math.ceil(L / e.params.slidesPerGroup)
                : e.snapGrid.length
        if (
            (e.params.loop
                ? ((T = e.previousRealIndex || 0),
                  (S =
                      e.params.slidesPerGroup > 1
                          ? Math.floor(e.realIndex / e.params.slidesPerGroup)
                          : e.realIndex))
                : typeof e.snapIndex < 'u'
                  ? ((S = e.snapIndex), (T = e.previousSnapIndex))
                  : ((T = e.previousIndex || 0), (S = e.activeIndex || 0)),
            p.type === 'bullets' &&
                e.pagination.bullets &&
                e.pagination.bullets.length > 0)
        ) {
            const b = e.pagination.bullets
            let k, E, w
            if (
                (p.dynamicBullets &&
                    ((n = re(b[0], e.isHorizontal() ? 'width' : 'height')),
                    m.forEach((C) => {
                        C.style[e.isHorizontal() ? 'width' : 'height'] =
                            `${n * (p.dynamicMainBullets + 4)}px`
                    }),
                    p.dynamicMainBullets > 1 &&
                        T !== void 0 &&
                        ((l += S - (T || 0)),
                        l > p.dynamicMainBullets - 1
                            ? (l = p.dynamicMainBullets - 1)
                            : l < 0 && (l = 0)),
                    (k = Math.max(S - l, 0)),
                    (E = k + (Math.min(b.length, p.dynamicMainBullets) - 1)),
                    (w = (E + k) / 2)),
                b.forEach((C) => {
                    const I = [
                        ...[
                            '',
                            '-next',
                            '-next-next',
                            '-prev',
                            '-prev-prev',
                            '-main',
                        ].map((A) => `${p.bulletActiveClass}${A}`),
                    ]
                        .map((A) =>
                            typeof A == 'string' && A.includes(' ')
                                ? A.split(' ')
                                : A,
                        )
                        .flat()
                    C.classList.remove(...I)
                }),
                m.length > 1)
            )
                b.forEach((C) => {
                    const I = Q(C)
                    ;(I === S
                        ? C.classList.add(...p.bulletActiveClass.split(' '))
                        : e.isElement && C.setAttribute('part', 'bullet'),
                        p.dynamicBullets &&
                            (I >= k &&
                                I <= E &&
                                C.classList.add(
                                    ...`${p.bulletActiveClass}-main`.split(' '),
                                ),
                            I === k && r(C, 'prev'),
                            I === E && r(C, 'next')))
                })
            else {
                const C = b[S]
                if (
                    (C && C.classList.add(...p.bulletActiveClass.split(' ')),
                    e.isElement &&
                        b.forEach((I, A) => {
                            I.setAttribute(
                                'part',
                                A === S ? 'bullet-active' : 'bullet',
                            )
                        }),
                    p.dynamicBullets)
                ) {
                    const I = b[k],
                        A = b[E]
                    for (let D = k; D <= E; D += 1)
                        b[D] &&
                            b[D].classList.add(
                                ...`${p.bulletActiveClass}-main`.split(' '),
                            )
                    ;(r(I, 'prev'), r(A, 'next'))
                }
            }
            if (p.dynamicBullets) {
                const C = Math.min(b.length, p.dynamicMainBullets + 4),
                    I = (n * C - n) / 2 - w * n,
                    A = c ? 'right' : 'left'
                b.forEach((D) => {
                    D.style[e.isHorizontal() ? A : 'top'] = `${I}px`
                })
            }
        }
        m.forEach((b, k) => {
            if (
                (p.type === 'fraction' &&
                    (b.querySelectorAll(j(p.currentClass)).forEach((E) => {
                        E.textContent = p.formatFractionCurrent(S + 1)
                    }),
                    b.querySelectorAll(j(p.totalClass)).forEach((E) => {
                        E.textContent = p.formatFractionTotal(P)
                    })),
                p.type === 'progressbar')
            ) {
                let E
                p.progressbarOpposite
                    ? (E = e.isHorizontal() ? 'vertical' : 'horizontal')
                    : (E = e.isHorizontal() ? 'horizontal' : 'vertical')
                const w = (S + 1) / P
                let C = 1,
                    I = 1
                ;(E === 'horizontal' ? (C = w) : (I = w),
                    b
                        .querySelectorAll(j(p.progressbarFillClass))
                        .forEach((A) => {
                            ;((A.style.transform = `translate3d(0,0,0) scaleX(${C}) scaleY(${I})`),
                                (A.style.transitionDuration = `${e.params.speed}ms`))
                        }))
            }
            ;(p.type === 'custom' && p.renderCustom
                ? (le(b, p.renderCustom(e, S + 1, P)),
                  k === 0 && i('paginationRender', b))
                : (k === 0 && i('paginationRender', b),
                  i('paginationUpdate', b)),
                e.params.watchOverflow &&
                    e.enabled &&
                    b.classList[e.isLocked ? 'add' : 'remove'](p.lockClass))
        })
    }
    function v() {
        const c = e.params.pagination
        if (o()) return
        const p =
            e.virtual && e.params.virtual.enabled
                ? e.virtual.slides.length
                : e.grid && e.params.grid.rows > 1
                  ? e.slides.length / Math.ceil(e.params.grid.rows)
                  : e.slides.length
        let m = e.pagination.el
        m = B(m)
        let S = ''
        if (c.type === 'bullets') {
            let T = e.params.loop
                ? Math.ceil(p / e.params.slidesPerGroup)
                : e.snapGrid.length
            e.params.freeMode && e.params.freeMode.enabled && T > p && (T = p)
            for (let L = 0; L < T; L += 1)
                c.renderBullet
                    ? (S += c.renderBullet.call(e, L, c.bulletClass))
                    : (S += `<${c.bulletElement} ${e.isElement ? 'part="bullet"' : ''} class="${c.bulletClass}"></${c.bulletElement}>`)
        }
        ;(c.type === 'fraction' &&
            (c.renderFraction
                ? (S = c.renderFraction.call(e, c.currentClass, c.totalClass))
                : (S = `<span class="${c.currentClass}"></span> / <span class="${c.totalClass}"></span>`)),
            c.type === 'progressbar' &&
                (c.renderProgressbar
                    ? (S = c.renderProgressbar.call(e, c.progressbarFillClass))
                    : (S = `<span class="${c.progressbarFillClass}"></span>`)),
            (e.pagination.bullets = []),
            m.forEach((T) => {
                ;(c.type !== 'custom' && le(T, S || ''),
                    c.type === 'bullets' &&
                        e.pagination.bullets.push(
                            ...T.querySelectorAll(j(c.bulletClass)),
                        ))
            }),
            c.type !== 'custom' && i('paginationRender', m[0]))
    }
    function d() {
        e.params.pagination = Pe(
            e,
            e.originalParams.pagination,
            e.params.pagination,
            { el: 'swiper-pagination' },
        )
        const c = e.params.pagination
        if (!c.el) return
        let p
        ;(typeof c.el == 'string' &&
            e.isElement &&
            (p = e.el.querySelector(c.el)),
            !p &&
                typeof c.el == 'string' &&
                (p = [...document.querySelectorAll(c.el)]),
            p || (p = c.el),
            !(!p || p.length === 0) &&
                (e.params.uniqueNavElements &&
                    typeof c.el == 'string' &&
                    Array.isArray(p) &&
                    p.length > 1 &&
                    ((p = [...e.el.querySelectorAll(c.el)]),
                    p.length > 1 &&
                        (p = p.find((m) => Te(m, '.swiper')[0] === e.el))),
                Array.isArray(p) && p.length === 1 && (p = p[0]),
                Object.assign(e.pagination, { el: p }),
                (p = B(p)),
                p.forEach((m) => {
                    ;(c.type === 'bullets' &&
                        c.clickable &&
                        m.classList.add(...(c.clickableClass || '').split(' ')),
                        m.classList.add(c.modifierClass + c.type),
                        m.classList.add(
                            e.isHorizontal()
                                ? c.horizontalClass
                                : c.verticalClass,
                        ),
                        c.type === 'bullets' &&
                            c.dynamicBullets &&
                            (m.classList.add(
                                `${c.modifierClass}${c.type}-dynamic`,
                            ),
                            (l = 0),
                            c.dynamicMainBullets < 1 &&
                                (c.dynamicMainBullets = 1)),
                        c.type === 'progressbar' &&
                            c.progressbarOpposite &&
                            m.classList.add(c.progressbarOppositeClass),
                        c.clickable && m.addEventListener('click', g),
                        e.enabled || m.classList.add(c.lockClass))
                })))
    }
    function h() {
        const c = e.params.pagination
        if (o()) return
        let p = e.pagination.el
        ;(p &&
            ((p = B(p)),
            p.forEach((m) => {
                ;(m.classList.remove(c.hiddenClass),
                    m.classList.remove(c.modifierClass + c.type),
                    m.classList.remove(
                        e.isHorizontal() ? c.horizontalClass : c.verticalClass,
                    ),
                    c.clickable &&
                        (m.classList.remove(
                            ...(c.clickableClass || '').split(' '),
                        ),
                        m.removeEventListener('click', g)))
            })),
            e.pagination.bullets &&
                e.pagination.bullets.forEach((m) =>
                    m.classList.remove(...c.bulletActiveClass.split(' ')),
                ))
    }
    ;(t('changeDirection', () => {
        if (!e.pagination || !e.pagination.el) return
        const c = e.params.pagination
        let { el: p } = e.pagination
        ;((p = B(p)),
            p.forEach((m) => {
                ;(m.classList.remove(c.horizontalClass, c.verticalClass),
                    m.classList.add(
                        e.isHorizontal() ? c.horizontalClass : c.verticalClass,
                    ))
            }))
    }),
        t('init', () => {
            e.params.pagination.enabled === !1 ? x() : (d(), v(), u())
        }),
        t('activeIndexChange', () => {
            typeof e.snapIndex > 'u' && u()
        }),
        t('snapIndexChange', () => {
            u()
        }),
        t('snapGridLengthChange', () => {
            ;(v(), u())
        }),
        t('destroy', () => {
            h()
        }),
        t('enable disable', () => {
            let { el: c } = e.pagination
            c &&
                ((c = B(c)),
                c.forEach((p) =>
                    p.classList[e.enabled ? 'remove' : 'add'](
                        e.params.pagination.lockClass,
                    ),
                ))
        }),
        t('lock unlock', () => {
            u()
        }),
        t('click', (c, p) => {
            const m = p.target,
                S = B(e.pagination.el)
            if (
                e.params.pagination.el &&
                e.params.pagination.hideOnClick &&
                S &&
                S.length > 0 &&
                !m.classList.contains(e.params.pagination.bulletClass)
            ) {
                if (
                    e.navigation &&
                    ((e.navigation.nextEl && m === e.navigation.nextEl) ||
                        (e.navigation.prevEl && m === e.navigation.prevEl))
                )
                    return
                const T = S[0].classList.contains(
                    e.params.pagination.hiddenClass,
                )
                ;(i(T === !0 ? 'paginationShow' : 'paginationHide'),
                    S.forEach((L) =>
                        L.classList.toggle(e.params.pagination.hiddenClass),
                    ))
            }
        }))
    const y = () => {
            e.el.classList.remove(e.params.pagination.paginationDisabledClass)
            let { el: c } = e.pagination
            ;(c &&
                ((c = B(c)),
                c.forEach((p) =>
                    p.classList.remove(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                d(),
                v(),
                u())
        },
        x = () => {
            e.el.classList.add(e.params.pagination.paginationDisabledClass)
            let { el: c } = e.pagination
            ;(c &&
                ((c = B(c)),
                c.forEach((p) =>
                    p.classList.add(
                        e.params.pagination.paginationDisabledClass,
                    ),
                )),
                h())
        }
    Object.assign(e.pagination, {
        enable: y,
        disable: x,
        render: v,
        update: u,
        init: d,
        destroy: h,
    })
}
function Qt({ swiper: e, extendParams: s, on: t, emit: i, params: a }) {
    ;((e.autoplay = { running: !1, paused: !1, timeLeft: 0 }),
        s({
            autoplay: {
                enabled: !1,
                delay: 3e3,
                waitForTransition: !0,
                disableOnInteraction: !1,
                stopOnLastSlide: !1,
                reverseDirection: !1,
                pauseOnMouseEnter: !1,
            },
        }))
    let n,
        l,
        o = a && a.autoplay ? a.autoplay.delay : 3e3,
        r = a && a.autoplay ? a.autoplay.delay : 3e3,
        f,
        g = new Date().getTime(),
        u,
        v,
        d,
        h,
        y,
        x
    function c(O) {
        !e ||
            e.destroyed ||
            !e.wrapperEl ||
            (O.target === e.wrapperEl &&
                (e.wrapperEl.removeEventListener('transitionend', c),
                !(x || (O.detail && O.detail.bySwiperTouchMove)) && k()))
    }
    const p = () => {
            if (e.destroyed || !e.autoplay.running) return
            e.autoplay.paused ? (u = !0) : u && ((r = f), (u = !1))
            const O = e.autoplay.paused ? f : g + r - new Date().getTime()
            ;((e.autoplay.timeLeft = O),
                i('autoplayTimeLeft', O, O / o),
                (l = requestAnimationFrame(() => {
                    p()
                })))
        },
        m = () => {
            let O
            return (
                e.virtual && e.params.virtual.enabled
                    ? (O = e.slides.find((z) =>
                          z.classList.contains('swiper-slide-active'),
                      ))
                    : (O = e.slides[e.activeIndex]),
                O
                    ? parseInt(O.getAttribute('data-swiper-autoplay'), 10)
                    : void 0
            )
        },
        S = () => {
            let O = e.params.autoplay.delay
            const M = m()
            return (!Number.isNaN(M) && M > 0 && (O = M), O)
        },
        T = (O) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(cancelAnimationFrame(l), p())
            let M = O
            ;(typeof M > 'u' && ((M = S()), (o = M), (r = M)), (f = M))
            const z = e.params.speed,
                G = () => {
                    !e ||
                        e.destroyed ||
                        (e.params.autoplay.reverseDirection
                            ? !e.isBeginning || e.params.loop || e.params.rewind
                                ? (e.slidePrev(z, !0, !0), i('autoplay'))
                                : e.params.autoplay.stopOnLastSlide ||
                                  (e.slideTo(e.slides.length - 1, z, !0, !0),
                                  i('autoplay'))
                            : !e.isEnd || e.params.loop || e.params.rewind
                              ? (e.slideNext(z, !0, !0), i('autoplay'))
                              : e.params.autoplay.stopOnLastSlide ||
                                (e.slideTo(0, z, !0, !0), i('autoplay')),
                        e.params.cssMode &&
                            ((g = new Date().getTime()),
                            requestAnimationFrame(() => {
                                T()
                            })))
                }
            return (
                M > 0
                    ? (clearTimeout(n),
                      (n = setTimeout(() => {
                          G()
                      }, M)))
                    : requestAnimationFrame(() => {
                          G()
                      }),
                M
            )
        },
        L = () => {
            ;((g = new Date().getTime()),
                (e.autoplay.running = !0),
                T(),
                i('autoplayStart'))
        },
        P = () => {
            ;((e.autoplay.running = !1),
                clearTimeout(n),
                cancelAnimationFrame(l),
                i('autoplayStop'))
        },
        b = (O, M) => {
            if (e.destroyed || !e.autoplay.running) return
            ;(clearTimeout(n), O || (y = !0))
            const z = () => {
                ;(i('autoplayPause'),
                    e.params.autoplay.waitForTransition
                        ? e.wrapperEl.addEventListener('transitionend', c)
                        : k())
            }
            if (((e.autoplay.paused = !0), M)) {
                z()
                return
            }
            ;((f = (f || e.params.autoplay.delay) - (new Date().getTime() - g)),
                !(e.isEnd && f < 0 && !e.params.loop) &&
                    (f < 0 && (f = 0), z()))
        },
        k = () => {
            ;(e.isEnd && f < 0 && !e.params.loop) ||
                e.destroyed ||
                !e.autoplay.running ||
                ((g = new Date().getTime()),
                y ? ((y = !1), T(f)) : T(),
                (e.autoplay.paused = !1),
                i('autoplayResume'))
        },
        E = () => {
            if (e.destroyed || !e.autoplay.running) return
            const O = N()
            ;(O.visibilityState === 'hidden' && ((y = !0), b(!0)),
                O.visibilityState === 'visible' && k())
        },
        w = (O) => {
            O.pointerType === 'mouse' &&
                ((y = !0),
                (x = !0),
                !(e.animating || e.autoplay.paused) && b(!0))
        },
        C = (O) => {
            O.pointerType === 'mouse' && ((x = !1), e.autoplay.paused && k())
        },
        I = () => {
            e.params.autoplay.pauseOnMouseEnter &&
                (e.el.addEventListener('pointerenter', w),
                e.el.addEventListener('pointerleave', C))
        },
        A = () => {
            e.el &&
                typeof e.el != 'string' &&
                (e.el.removeEventListener('pointerenter', w),
                e.el.removeEventListener('pointerleave', C))
        },
        D = () => {
            N().addEventListener('visibilitychange', E)
        },
        _ = () => {
            N().removeEventListener('visibilitychange', E)
        }
    ;(t('init', () => {
        e.params.autoplay.enabled && (I(), D(), L())
    }),
        t('destroy', () => {
            ;(A(), _(), e.autoplay.running && P())
        }),
        t('_freeModeStaticRelease', () => {
            ;(d || y) && k()
        }),
        t('_freeModeNoMomentumRelease', () => {
            e.params.autoplay.disableOnInteraction ? P() : b(!0, !0)
        }),
        t('beforeTransitionStart', (O, M, z) => {
            e.destroyed ||
                !e.autoplay.running ||
                (z || !e.params.autoplay.disableOnInteraction ? b(!0, !0) : P())
        }),
        t('sliderFirstMove', () => {
            if (!(e.destroyed || !e.autoplay.running)) {
                if (e.params.autoplay.disableOnInteraction) {
                    P()
                    return
                }
                ;((v = !0),
                    (d = !1),
                    (y = !1),
                    (h = setTimeout(() => {
                        ;((y = !0), (d = !0), b(!0))
                    }, 200)))
            }
        }),
        t('touchEnd', () => {
            if (!(e.destroyed || !e.autoplay.running || !v)) {
                if (
                    (clearTimeout(h),
                    clearTimeout(n),
                    e.params.autoplay.disableOnInteraction)
                ) {
                    ;((d = !1), (v = !1))
                    return
                }
                ;(d && e.params.cssMode && k(), (d = !1), (v = !1))
            }
        }),
        t('slideChange', () => {
            e.destroyed ||
                !e.autoplay.running ||
                (e.autoplay.paused && ((f = S()), (o = S())))
        }),
        Object.assign(e.autoplay, { start: L, stop: P, pause: b, resume: k }))
}
function Zt(e) {
    const {
        effect: s,
        swiper: t,
        on: i,
        setTranslate: a,
        setTransition: n,
        overwriteParams: l,
        perspective: o,
        recreateShadows: r,
        getEffectParams: f,
    } = e
    ;(i('beforeInit', () => {
        if (t.params.effect !== s) return
        ;(t.classNames.push(`${t.params.containerModifierClass}${s}`),
            o &&
                o() &&
                t.classNames.push(`${t.params.containerModifierClass}3d`))
        const u = l ? l() : {}
        ;(Object.assign(t.params, u), Object.assign(t.originalParams, u))
    }),
        i('setTranslate _virtualUpdated', () => {
            t.params.effect === s && a()
        }),
        i('setTransition', (u, v) => {
            t.params.effect === s && n(v)
        }),
        i('transitionEnd', () => {
            if (t.params.effect === s && r) {
                if (!f || !f().slideShadows) return
                ;(t.slides.forEach((u) => {
                    u.querySelectorAll(
                        '.swiper-slide-shadow-top, .swiper-slide-shadow-right, .swiper-slide-shadow-bottom, .swiper-slide-shadow-left',
                    ).forEach((v) => v.remove())
                }),
                    r())
            }
        }))
    let g
    i('virtualUpdate', () => {
        t.params.effect === s &&
            (t.slides.length || (g = !0),
            requestAnimationFrame(() => {
                g && t.slides && t.slides.length && (a(), (g = !1))
            }))
    })
}
function es(e, s) {
    const t = be(s)
    return (
        t !== s &&
            ((t.style.backfaceVisibility = 'hidden'),
            (t.style['-webkit-backface-visibility'] = 'hidden')),
        t
    )
}
function ts({ swiper: e, duration: s, transformElements: t, allSlides: i }) {
    const { activeIndex: a } = e
    if (e.params.virtualTranslate && s !== 0) {
        let n = !1,
            l
        ;((l = t),
            l.forEach((o) => {
                _e(o, () => {
                    if (n || !e || e.destroyed) return
                    ;((n = !0), (e.animating = !1))
                    const r = new window.CustomEvent('transitionend', {
                        bubbles: !0,
                        cancelable: !0,
                    })
                    e.wrapperEl.dispatchEvent(r)
                })
            }))
    }
}
function ss({ swiper: e, extendParams: s, on: t }) {
    ;(s({ fadeEffect: { crossFade: !1 } }),
        Zt({
            effect: 'fade',
            swiper: e,
            on: t,
            setTranslate: () => {
                const { slides: n } = e,
                    l = e.params.fadeEffect
                for (let o = 0; o < n.length; o += 1) {
                    const r = e.slides[o]
                    let g = -r.swiperSlideOffset
                    e.params.virtualTranslate || (g -= e.translate)
                    let u = 0
                    e.isHorizontal() || ((u = g), (g = 0))
                    const v = e.params.fadeEffect.crossFade
                            ? Math.max(1 - Math.abs(r.progress), 0)
                            : 1 + Math.min(Math.max(r.progress, -1), 0),
                        d = es(l, r)
                    ;((d.style.opacity = v),
                        (d.style.transform = `translate3d(${g}px, ${u}px, 0px)`))
                }
            },
            setTransition: (n) => {
                const l = e.slides.map((o) => be(o))
                ;(l.forEach((o) => {
                    o.style.transitionDuration = `${n}ms`
                }),
                    ts({
                        swiper: e,
                        duration: n,
                        transformElements: l,
                        allSlides: !0,
                    }))
            },
            overwriteParams: () => ({
                slidesPerView: 1,
                slidesPerGroup: 1,
                watchSlidesProgress: !0,
                spaceBetween: 0,
                virtualTranslate: !e.params.cssMode,
            }),
        }))
}
function Le() {
    document.querySelectorAll('.swiper').forEach((s) => {
        s.dataset.initialized || ((s.dataset.initialized = !0), is(s))
    })
}
Le()
document.addEventListener('livewire:navigated', () => {
    Le()
})
function is(e) {
    let s = e.querySelector('.swiper-controls')
    s || (s = e.parentNode.querySelector('.swiper-controls'))
    const t = s?.querySelector('.swiper-pagination'),
        i = s?.querySelector('.swiper-button-prev'),
        a = s?.querySelector('.swiper-button-next'),
        n = e.dataset.fade === '1',
        l = e.dataset.loop === '1',
        o = e.dataset.auto === '1',
        r = parseInt(e.dataset.delay, 10) || 5e3,
        f = e.dataset.drag !== void 0 ? e.dataset.drag === '1' : !0,
        g = e.dataset.align || 'center',
        u = parseInt(e.dataset.perview, 10) || 1
    let v = [],
        d = {
            loop: l,
            centeredSlides: g === 'center',
            slidesPerView: u,
            spaceBetween: 0,
            speed: 300,
            allowTouchMove: f,
            grabCursor: f,
            watchSlidesProgress: !0,
            on: {
                init: function () {
                    ;(e.classList.add('swiper-ready'), y(this))
                },
                slideChange: function () {
                    y(this)
                },
                resize: function () {
                    this.update()
                },
            },
        }
    if (e.dataset.breakpoint)
        try {
            d.breakpoints = JSON.parse(e.dataset.breakpoint)
        } catch (m) {
            console.error('Invalid JSON in data-breakpoint:', m)
        }
    ;((i || a) &&
        (v.push(Kt),
        (d.navigation = {
            nextEl: a,
            prevEl: i,
            disabledClass: 'swiper-button-disabled',
        })),
        t &&
            (v.push(Jt),
            (d.pagination = {
                el: t,
                clickable: !0,
                bulletClass: 'swiper-pagination-bullet',
                bulletActiveClass: 'swiper-pagination-bullet-active',
            })),
        o &&
            (v.push(Qt),
            (d.autoplay = {
                delay: r,
                stopOnLastSlide: !1,
                disableOnInteraction: !0,
                pauseOnMouseEnter: !0,
            })),
        n &&
            (v.push(ss),
            (d.effect = 'fade'),
            (d.fadeEffect = { crossFade: !0 }),
            (d.slidesPerView = 1),
            (d.centeredSlides = !0)),
        (d.modules = v))
    let h = new F(e, d)
    function y(m) {
        m.slides.forEach((T, L) => {
            ;(T.classList.remove('swiper-slide-selected'),
                L === m.activeIndex && T.classList.add('swiper-slide-selected'))
        })
    }
    function x(m) {
        ;(e.classList.toggle('swiper-disabled', !m),
            h.autoplay && (m ? h.autoplay.start() : h.autoplay.stop()))
    }
    ;(new IntersectionObserver((m) => {
        m.forEach((S) => {
            S.isIntersecting ? x(!0) : x(!1)
        })
    }).observe(e),
        e.querySelectorAll('img').forEach((m) => {
            m.addEventListener('load', () => {
                ;(h.update(), x(!0))
            })
        }),
        e.addEventListener('enable-carousel', () => x(!0), !1),
        e.addEventListener('disable-carousel', () => x(!1), !1),
        (e.swiper = h))
}
