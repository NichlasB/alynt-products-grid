export function applyResponsiveBreakpoints(instance) {
    const styleId = `alynt-pg-responsive-${instance.settings.columns}-${instance.settings.breakpoint5}-${instance.settings.breakpoint4}-${instance.settings.breakpoint3}-${instance.settings.breakpoint2}`;

    if (document.getElementById(styleId)) {
        return;
    }

    const style = document.createElement('style');
    style.id = styleId;
    style.textContent = `
        @media (max-width: ${instance.settings.breakpoint5}px) {
            .alynt-pg-container[data-columns="${instance.settings.columns}"] .alynt-pg-products-grid {
                grid-template-columns: repeat(${Math.min(4, instance.settings.columns)}, 1fr);
            }
        }
        @media (max-width: ${instance.settings.breakpoint4}px) {
            .alynt-pg-container[data-columns="${instance.settings.columns}"] .alynt-pg-products-grid {
                grid-template-columns: repeat(${Math.min(3, instance.settings.columns)}, 1fr);
            }
        }
        @media (max-width: ${instance.settings.breakpoint3}px) {
            .alynt-pg-container[data-columns="${instance.settings.columns}"] .alynt-pg-products-grid {
                grid-template-columns: repeat(${Math.min(2, instance.settings.columns)}, 1fr);
            }
        }
        @media (max-width: ${instance.settings.breakpoint2}px) {
            .alynt-pg-container[data-columns="${instance.settings.columns}"] .alynt-pg-products-grid {
                grid-template-columns: 1fr;
            }
        }
    `;
    document.head.appendChild(style);
}
