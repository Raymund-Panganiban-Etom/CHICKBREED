    // Intersection Observer for fade-up animations
    const fadeElements = document.querySelectorAll('.fade-up');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });
    fadeElements.forEach(el => observer.observe(el));

    // Animated counter when counters section becomes visible
    const counters = document.querySelectorAll('.counter');
    let counted = false;
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !counted) {
                counted = true;
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-target'));
                    let current = 0;
                    const increment = target / 50;
                    const updateCounter = () => {
                        current += increment;
                        if (current < target) {
                            counter.innerText = Math.floor(current);
                            requestAnimationFrame(updateCounter);
                        } else {
                            counter.innerText = target;
                        }
                    };
                    updateCounter();
                });
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    const counterSection = document.querySelector('.counter-section');
    if (counterSection) counterObserver.observe(counterSection);
