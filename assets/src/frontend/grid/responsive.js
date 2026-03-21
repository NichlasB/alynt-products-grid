export function applyResponsiveBreakpoints(instance) {
    const style = document.createElement('style');
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
