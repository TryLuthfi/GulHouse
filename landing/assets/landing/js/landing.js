(function () {
    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var header = document.querySelector('.site-header');

    function setHeaderState() {
        if (header) {
            header.classList.toggle('is-scrolled', window.scrollY > 12);
        }
    }

    function initPageProgress() {
        var progress = document.querySelector('[data-page-progress]');

        if (!progress) {
            return;
        }

        function update() {
            var scrollable = Math.max(1, document.documentElement.scrollHeight - window.innerHeight);
            var value = Math.min(1, Math.max(0, window.scrollY / scrollable));
            progress.style.transform = 'scaleX(' + value + ')';
        }

        window.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    }

    function initScrollSpy() {
        var navItems = Array.prototype.slice.call(document.querySelectorAll('.nav-links a[href^="#"]'));

        if (!navItems.length || !('IntersectionObserver' in window)) {
            return;
        }

        var byId = {};

        navItems.forEach(function (link) {
            var id = link.getAttribute('href').replace('#', '');
            var section = document.getElementById(id);

            if (section) {
                byId[id] = link;
            }
        });

        function setActive(id) {
            navItems.forEach(function (link) {
                link.classList.toggle('is-active', link === byId[id]);
            });
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    setActive(entry.target.id);
                }
            });
        }, { rootMargin: '-42% 0px -48% 0px', threshold: 0.01 });

        Object.keys(byId).forEach(function (id) {
            observer.observe(document.getElementById(id));
        });
    }

    function initMobileMenu() {
        var toggle = document.querySelector('[data-menu-toggle]');
        var links = document.querySelectorAll('.nav-links a');

        if (!header || !toggle) {
            return;
        }

        function setOpen(open) {
            header.classList.toggle('is-menu-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        toggle.addEventListener('click', function () {
            setOpen(!header.classList.contains('is-menu-open'));
        });

        links.forEach(function (link) {
            link.addEventListener('click', function () {
                setOpen(false);
            });
        });
    }

    function initDetailStickyBar() {
        var stickyBar = document.querySelector('[data-detail-sticky]');
        var hero = document.querySelector('.detail-hero');

        if (!stickyBar || !hero) {
            return;
        }

        var ticking = false;

        function update() {
            var rect = hero.getBoundingClientRect();
            var visible = rect.bottom <= 96;
            stickyBar.classList.toggle('is-visible', visible);
            stickyBar.setAttribute('aria-hidden', visible ? 'false' : 'true');
            ticking = false;
        }

        function requestUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        update();
    }

    function initReveal() {
        var items = document.querySelectorAll('.reveal');

        if (prefersReducedMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (item) {
                item.classList.add('is-visible');
            });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.16 });

        items.forEach(function (item) {
            observer.observe(item);
        });
    }

    function initParallax() {
        var sections = document.querySelectorAll('[data-parallax]');
        var cards = document.querySelectorAll('[data-parallax-card]');

        if (prefersReducedMotion || (!sections.length && !cards.length)) {
            return;
        }

        var ticking = false;

        function update() {
            var viewportH = window.innerHeight || 1;

            sections.forEach(function (section) {
                var rect = section.getBoundingClientRect();
                var speed = parseFloat(section.getAttribute('data-parallax-speed') || '0.22');
                var offset = Math.round(rect.top * speed * -1);
                section.style.setProperty('--parallax-y', offset + 'px');
            });

            cards.forEach(function (card) {
                var rect = card.getBoundingClientRect();
                var progress = (rect.top - viewportH / 2) / viewportH;
                var offset = Math.max(-22, Math.min(22, Math.round(progress * -34)));
                card.style.setProperty('--card-parallax-y', offset + 'px');
            });

            ticking = false;
        }

        function requestUpdate() {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        }

        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        update();
    }

    function initHeroSlider() {
        var slider = document.querySelector('[data-hero-slider]');

        if (!slider) {
            return;
        }

        var slides = Array.prototype.slice.call(slider.querySelectorAll('.hero-slide'));
        var dotsWrap = slider.querySelector('[data-hero-dots]');
        var progress = slider.querySelector('[data-hero-progress]');
        var dots = [];
        var active = 0;
        var delay = 7200;
        var timer = null;
        var paused = document.hidden;

        if (slides.length < 2) {
            return;
        }

        slides.forEach(function (slide) {
            var image = window.getComputedStyle(slide).backgroundImage;
            var match = image.match(/url\(["']?(.+?)["']?\)/);

            if (match && match[1]) {
                var preload = new Image();
                preload.src = match[1];
            }
        });

        function restartProgress() {
            if (!progress || prefersReducedMotion) {
                return;
            }

            progress.classList.remove('is-running');
            progress.style.animation = 'none';
            window.setTimeout(function () {
                progress.style.animation = '';
                progress.classList.add('is-running');
            }, 20);
        }

        function setActive(index) {
            active = (index + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                slide.classList.toggle('is-active', slideIndex === active);
            });

            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === active);
            });

            restartProgress();
        }

        function clearTimer() {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
        }

        function schedule() {
            clearTimer();

            if (prefersReducedMotion || paused) {
                return;
            }

            timer = window.setTimeout(function () {
                setActive(active + 1);
                schedule();
            }, delay);
        }

        if (dotsWrap) {
            slides.forEach(function (_, index) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Lihat foto utama ' + (index + 1));
                dot.addEventListener('click', function () {
                    setActive(index);
                    schedule();
                });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            });
        }

        slider.addEventListener('focusin', function () {
            paused = true;
            clearTimer();
        });

        slider.addEventListener('focusout', function () {
            paused = false;
            schedule();
        });

        document.addEventListener('visibilitychange', function () {
            paused = document.hidden;

            if (paused) {
                clearTimer();
            } else {
                schedule();
            }
        });

        setActive(0);
        schedule();
    }

    function initCarousel() {
        var carousel = document.querySelector('[data-carousel]');
        var prev = document.querySelector('[data-carousel-prev]');
        var next = document.querySelector('[data-carousel-next]');
        var dotsWrap = document.querySelector('[data-carousel-dots]');

        if (!carousel) {
            return;
        }

        var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-carousel-slide]'));
        var dots = [];

        function slideWidth() {
            return slides[0] ? slides[0].getBoundingClientRect().width + 18 : carousel.clientWidth;
        }

        function activeIndex() {
            return Math.round(carousel.scrollLeft / slideWidth());
        }

        function updateDots() {
            var index = activeIndex();
            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        }

        if (dotsWrap) {
            slides.forEach(function (_, index) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Lihat kamar ' + (index + 1));
                dot.addEventListener('click', function () {
                    carousel.scrollTo({ left: slideWidth() * index, behavior: 'smooth' });
                });
                dotsWrap.appendChild(dot);
                dots.push(dot);
            });
        }

        if (prev) {
            prev.addEventListener('click', function () {
                carousel.scrollBy({ left: -slideWidth(), behavior: 'smooth' });
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                carousel.scrollBy({ left: slideWidth(), behavior: 'smooth' });
            });
        }

        carousel.addEventListener('scroll', function () {
            window.requestAnimationFrame(updateDots);
        }, { passive: true });

        updateDots();
    }

    function initAccordion() {
        document.querySelectorAll('[data-accordion]').forEach(function (accordion) {
            accordion.querySelectorAll('.accordion-item').forEach(function (item) {
                var trigger = item.querySelector('.accordion-trigger');
                var panel = item.querySelector('.accordion-panel');

                if (!trigger || !panel) {
                    return;
                }

                function setState(open) {
                    item.classList.toggle('is-open', open);
                    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
                    panel.style.maxHeight = open ? panel.scrollHeight + 'px' : '0px';
                }

                trigger.addEventListener('click', function () {
                    var willOpen = !item.classList.contains('is-open');

                    accordion.querySelectorAll('.accordion-item').forEach(function (other) {
                        var otherPanel = other.querySelector('.accordion-panel');
                        var otherTrigger = other.querySelector('.accordion-trigger');
                        other.classList.remove('is-open');
                        if (otherPanel) {
                            otherPanel.style.maxHeight = '0px';
                        }
                        if (otherTrigger) {
                            otherTrigger.setAttribute('aria-expanded', 'false');
                        }
                    });

                    setState(willOpen);
                });

                setState(item.classList.contains('is-open'));
            });
        });
    }

    function initLottie() {
        var target = document.getElementById('availability-lottie');

        if (!target || !window.lottie || prefersReducedMotion) {
            return;
        }

        window.lottie.loadAnimation({
            container: target,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            animationData: {
                v: '5.7.4',
                fr: 30,
                ip: 0,
                op: 90,
                w: 120,
                h: 120,
                nm: 'GUL HOUSE pulse',
                ddd: 0,
                assets: [],
                layers: [
                    {
                        ddd: 0,
                        ind: 1,
                        ty: 4,
                        nm: 'pulse ring',
                        sr: 1,
                        ks: {
                            o: { a: 1, k: [{ t: 0, s: [60] }, { t: 45, s: [10] }, { t: 90, s: [60] }] },
                            r: { a: 0, k: 0 },
                            p: { a: 0, k: [60, 60, 0] },
                            a: { a: 0, k: [0, 0, 0] },
                            s: { a: 1, k: [{ t: 0, s: [74, 74, 100] }, { t: 45, s: [112, 112, 100] }, { t: 90, s: [74, 74, 100] }] }
                        },
                        shapes: [
                            { ty: 'el', p: { a: 0, k: [0, 0] }, s: { a: 0, k: [62, 62] }, nm: 'circle' },
                            { ty: 'st', c: { a: 0, k: [0.788, 0.592, 0.298, 1] }, o: { a: 0, k: 100 }, w: { a: 0, k: 5 }, lc: 2, lj: 2, nm: 'stroke' },
                            { ty: 'tr', p: { a: 0, k: [0, 0] }, a: { a: 0, k: [0, 0] }, s: { a: 0, k: [100, 100] }, r: { a: 0, k: 0 }, o: { a: 0, k: 100 } }
                        ],
                        ip: 0,
                        op: 90,
                        st: 0,
                        bm: 0
                    },
                    {
                        ddd: 0,
                        ind: 2,
                        ty: 4,
                        nm: 'core',
                        sr: 1,
                        ks: {
                            o: { a: 0, k: 100 },
                            r: { a: 0, k: 0 },
                            p: { a: 0, k: [60, 60, 0] },
                            a: { a: 0, k: [0, 0, 0] },
                            s: { a: 1, k: [{ t: 0, s: [92, 92, 100] }, { t: 45, s: [104, 104, 100] }, { t: 90, s: [92, 92, 100] }] }
                        },
                        shapes: [
                            { ty: 'el', p: { a: 0, k: [0, 0] }, s: { a: 0, k: [42, 42] }, nm: 'circle' },
                            { ty: 'fl', c: { a: 0, k: [0.09, 0.247, 0.208, 1] }, o: { a: 0, k: 100 }, r: 1, nm: 'fill' },
                            { ty: 'tr', p: { a: 0, k: [0, 0] }, a: { a: 0, k: [0, 0] }, s: { a: 0, k: [100, 100] }, r: { a: 0, k: 0 }, o: { a: 0, k: 100 } }
                        ],
                        ip: 0,
                        op: 90,
                        st: 0,
                        bm: 0
                    }
                ]
            }
        });
    }

    function initPhotoModal() {
        var modal = document.querySelector('[data-photo-modal]');
        var galleryData = window.GH_ROOM_GALLERY || [];

        if (!modal || !galleryData.length) {
            return;
        }

        var mainImage = modal.querySelector('[data-photo-main]');
        var title = modal.querySelector('[data-photo-title]');
        var category = modal.querySelector('[data-photo-category]');
        var count = modal.querySelector('[data-photo-count]');
        var thumbs = modal.querySelector('[data-photo-thumbs]');
        var tabs = modal.querySelector('[data-photo-tabs]');
        var prev = modal.querySelector('[data-photo-prev]');
        var next = modal.querySelector('[data-photo-next]');
        var activeCategory = 'Semua foto';
        var activeIndex = 0;

        function filteredItems() {
            if (activeCategory === 'Semua foto') {
                return galleryData;
            }

            return galleryData.filter(function (item) {
                return item.category === activeCategory;
            });
        }

        function categories() {
            var result = ['Semua foto'];

            galleryData.forEach(function (item) {
                if (result.indexOf(item.category) === -1) {
                    result.push(item.category);
                }
            });

            return result;
        }

        function renderTabs() {
            tabs.innerHTML = '';

            categories().forEach(function (name) {
                var total = name === 'Semua foto'
                    ? galleryData.length
                    : galleryData.filter(function (item) { return item.category === name; }).length;
                var button = document.createElement('button');
                button.type = 'button';
                button.textContent = name + ' (' + total + ')';
                button.classList.toggle('is-active', name === activeCategory);
                button.addEventListener('click', function () {
                    activeCategory = name;
                    activeIndex = 0;
                    render();
                });
                tabs.appendChild(button);
            });
        }

        function renderThumbs(items) {
            thumbs.innerHTML = '';

            items.forEach(function (item, index) {
                var button = document.createElement('button');
                var image = document.createElement('img');
                button.type = 'button';
                button.classList.toggle('is-active', index === activeIndex);
                image.src = item.src;
                image.alt = item.title;
                button.appendChild(image);
                button.addEventListener('click', function () {
                    activeIndex = index;
                    render();
                });
                thumbs.appendChild(button);
            });
        }

        function render() {
            var items = filteredItems();
            var current = items[activeIndex] || items[0];

            if (!current) {
                return;
            }

            mainImage.src = current.src;
            mainImage.alt = current.title;
            title.textContent = current.title;
            category.textContent = current.category;
            count.textContent = (activeIndex + 1) + '/' + items.length;
            renderTabs();
            renderThumbs(items);
        }

        function openModal(index) {
            activeCategory = 'Semua foto';
            activeIndex = Math.max(0, Math.min(index, galleryData.length - 1));
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            render();
        }

        function closeModal() {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function move(direction) {
            var items = filteredItems();
            activeIndex = (activeIndex + direction + items.length) % items.length;
            render();
        }

        document.querySelectorAll('[data-gallery-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                openModal(parseInt(button.getAttribute('data-gallery-open'), 10) || 0);
            });
        });

        modal.querySelectorAll('[data-photo-close]').forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        if (prev) {
            prev.addEventListener('click', function () { move(-1); });
        }

        if (next) {
            next.addEventListener('click', function () { move(1); });
        }

        document.addEventListener('keydown', function (event) {
            if (!modal.classList.contains('is-open')) {
                return;
            }

            if (event.key === 'Escape') {
                closeModal();
            } else if (event.key === 'ArrowLeft') {
                move(-1);
            } else if (event.key === 'ArrowRight') {
                move(1);
            }
        });
    }

    function initRoomFilter() {
        var filter = document.querySelector('[data-room-filter]');
        var cards = Array.prototype.slice.call(document.querySelectorAll('[data-room-card]'));
        var empty = document.querySelector('[data-room-empty]');

        if (!filter || !cards.length) {
            return;
        }

        filter.querySelectorAll('button').forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-filter');

                filter.querySelectorAll('button').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });

                cards.forEach(function (card) {
                    var match = value === 'all'
                        || card.getAttribute('data-status') === value
                        || card.getAttribute('data-property') === value
                        || card.getAttribute('data-type') === value;

                    card.classList.toggle('is-hidden', !match);
                });

                if (empty) {
                    var visibleCards = cards.filter(function (card) {
                        return !card.classList.contains('is-hidden');
                    });
                    empty.classList.toggle('is-visible', visibleCards.length === 0);
                    empty.setAttribute('aria-hidden', visibleCards.length === 0 ? 'false' : 'true');
                }
            });
        });
    }

    setHeaderState();
    window.addEventListener('scroll', setHeaderState, { passive: true });
    initPageProgress();
    initScrollSpy();
    initMobileMenu();
    initReveal();
    initParallax();
    initHeroSlider();
    initCarousel();
    initAccordion();
    initLottie();
    initDetailStickyBar();
    initPhotoModal();
    initRoomFilter();
}());
