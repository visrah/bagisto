/**
 * This will track all the images and fonts for publishing.
 */
import.meta.glob(["../images/**", "../fonts/**"]);

/**
 * Main vue bundler.
 */
import { createApp } from "vue/dist/vue.esm-bundler";

/**
 * Main root application registry.
 */
window.app = createApp({
    data() {
        return {};
    },

    methods: {
        onSubmit() {},

        onInvalidSubmit({ values, errors, results }) {
            setTimeout(() => {
                const errorKeys = Object.entries(errors)
                    .map(([key, value]) => ({ key, value }))
                    .filter(error => error["value"].length);

                if (errorKeys.length > 0) {
                    const errorKey = errorKeys[0]["key"];

                    let scrollTarget = null;

                    let firstErrorElement = document.querySelector('[name="' + errorKey + '"]');

                    if (
                        ! firstErrorElement 
                        && ! errorKey.endsWith('[]')
                    ) {
                        firstErrorElement = document.querySelector('[name="' + errorKey + '[]"]');
                    }

                    if (! firstErrorElement) {
                        firstErrorElement = document.querySelector('[name^="' + errorKey + '"]');
                    }

                    if (firstErrorElement) {

                        if (firstErrorElement.tagName === 'TEXTAREA' && firstErrorElement.style.display === 'none') {

                            const editorId = firstErrorElement.id;

                            const tinyMCEContainer = document.querySelector('#' + editorId + '_parent');
                            
                            if (tinyMCEContainer) {
                                scrollTarget = tinyMCEContainer;
                            } else {
                                scrollTarget = firstErrorElement;
                            }
                        } else {
                            scrollTarget = firstErrorElement;
                        }
                    } else {


                        const errorMessageElement = document.querySelector('[name="' + errorKey + '"] p, [name="' + errorKey + '[]"] p');
                        
                        if (errorMessageElement) {

                            scrollTarget = errorMessageElement.closest('.box-shadow') || errorMessageElement.closest('div[class*="bg-white"]') || errorMessageElement;
                        }
                    }

                    if (scrollTarget) {
                        let ancestor = scrollTarget.parentElement;

                        while (ancestor) {
                            if (ancestor.tagName === 'DETAILS') {
                                ancestor.open = true;
                            }

                            ancestor = ancestor.parentElement;
                        }

                        scrollTarget.scrollIntoView({
                            behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "instant" : "smooth",
                            block: "center"
                        });

                        if (firstErrorElement) {
                            if (firstErrorElement.tagName === 'TEXTAREA' && firstErrorElement.style.display === 'none') {

                                const editorId = firstErrorElement.id;

                                if (window.tinymce && tinymce.get(editorId)) {
                                    tinymce.get(editorId).focus();
                                }
                            } else if (firstErrorElement.focus) {
                                firstErrorElement.focus();
                            }
                        }
                    } else {

                        const allErrors = errorKeys
                            .map(error => {
                                if (Array.isArray(error.value)) {
                                    return error.value.join(', ');
                                }

                                return error.value;
                            })
                            .filter(msg => msg).join(' ');
                        
                        this.$emitter.emit('add-flash', { 
                            type: 'error', 
                            message: allErrors 
                        });
                    }
                }
            }, 100);
        },
    },
});

/**
 * Global plugins registration.
 */
import Admin from "./plugins/admin";
import Axios from "./plugins/axios";
import CreateElement from "./plugins/createElement";
import Emitter from "./plugins/emitter";
import Flatpickr from "./plugins/flatpickr";
import VeeValidate from "./plugins/vee-validate";
import Draggable from "./plugins/draggable";
import VueCal from 'vue-cal';
import 'vue-cal/dist/vuecal.css';

app.component('vue-cal', VueCal);

[
    Admin,
    Axios,
    CreateElement,
    Emitter,
    Flatpickr,
    VeeValidate,
    Draggable,
].forEach((plugin) => app.use(plugin));

/**
 * Global directives.
 */
import Slugify from "./directives/slugify";
import SlugifyTarget from "./directives/slugify-target";
import Debounce from "./directives/debounce";
import Code from "./directives/code";

app.directive("slugify", Slugify);
app.directive("slugify-target", SlugifyTarget);
app.directive("debounce", Debounce);
app.directive("code", Code);

export default app;
