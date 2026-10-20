<?php if (! $__env->hasRenderedOnce('3ed5b7d1-b715-4f24-a15b-261d5ce5e62a')): $__env->markAsRenderedOnce('3ed5b7d1-b715-4f24-a15b-261d5ce5e62a'); ?>
    <?php $__env->startPush('scripts'); ?>
        <script>
            (function initUiTooltipProvider() {
                if (window.__uiTooltipProviderReady) return;
                window.__uiTooltipProviderReady = true;

                const ATTR = 'data-ui-tooltip';
                const PLACEMENT_ATTR = 'data-ui-tooltip-placement';
                const SHOW_CLS = ['opacity-100', 'visible'];
                const HIDE_CLS = ['opacity-0', 'invisible'];
                const BASE_CLS = [
                    'fixed',
                    'left-0',
                    'top-0',
                    'z-[2147483646]',
                    'box-border',
                    'max-w-[min(280px,calc(100vw-16px))]',
                    'rounded-xl',
                    'border',
                    'border-white/15',
                    'bg-slate-900/95',
                    'px-2.5',
                    'py-2',
                    'text-center',
                    'text-[11px]',
                    'font-extrabold',
                    'leading-tight',
                    'tracking-wide',
                    'text-white',
                    'pointer-events-none',
                    'shadow-2xl',
                    'shadow-slate-900/60',
                    'transition-none',
                ];

                const tip = document.createElement('div');
                tip.id = 'ui-global-tooltip';
                tip.setAttribute('role', 'tooltip');
                tip.classList.add(...BASE_CLS, ...HIDE_CLS);

                const content = document.createElement('span');
                tip.appendChild(content);

                const arrow = document.createElement('span');
                tip.appendChild(arrow);
                document.body.appendChild(tip);

                let activeEl = null;
                const normalizePlacement = (placement) => placement === 'bottom' ? 'bottom' : 'top';

                const applyPlacementStyles = (placement) => {
                    if (placement === 'bottom') {
                        arrow.className = 'absolute left-1/2 bottom-full -mb-px h-0 w-0 -translate-x-1/2 border-x-[5px] border-x-transparent border-b-[5px] border-b-slate-900/95';
                        return;
                    }
                    arrow.className = 'absolute left-1/2 top-full -mt-px h-0 w-0 -translate-x-1/2 border-x-[5px] border-x-transparent border-t-[5px] border-t-slate-900/95';
                };

                const place = (el) => {
                    const rect = el.getBoundingClientRect();
                    const placement = normalizePlacement(el.getAttribute(PLACEMENT_ATTR));
                    applyPlacementStyles(placement);
                    tip.style.left = `${rect.left + rect.width / 2}px`;
                    if (placement === 'bottom') {
                        tip.style.top = `${rect.bottom}px`;
                        tip.style.transform = 'translate(-50%, 10px)';
                        return;
                    }
                    tip.style.top = `${rect.top}px`;
                    tip.style.transform = 'translate(-50%, calc(-100% - 10px))';
                };

                const show = (el) => {
                    const text = el.getAttribute(ATTR);
                    if (!text) return;
                    activeEl = el;
                    content.textContent = text;
                    place(el);
                    tip.classList.remove(...HIDE_CLS);
                    tip.classList.add(...SHOW_CLS);
                };

                const hide = () => {
                    activeEl = null;
                    tip.classList.remove(...SHOW_CLS);
                    tip.classList.add(...HIDE_CLS);
                };

                document.addEventListener('mouseover', (e) => {
                    const el = e.target.closest?.(`[${ATTR}]`);
                    if (el) show(el);
                }, true);

                document.addEventListener('mouseout', (e) => {
                    const host = e.target.closest?.(`[${ATTR}]`);
                    if (!host) return;

                    const related = e.relatedTarget;
                    if (related && host.contains(related)) return;

                    const next = related && related.nodeType === 1 ? related.closest(`[${ATTR}]`) : null;
                    if (next && next !== host) {
                        show(next);
                        return;
                    }
                    hide();
                }, true);

                window.addEventListener('scroll', () => {
                    if (activeEl) place(activeEl);
                }, true);

                window.addEventListener('resize', hide);
            })();
        </script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH /var/www/application/withdraw/resources/views/components/ui/tooltip-provider.blade.php ENDPATH**/ ?>