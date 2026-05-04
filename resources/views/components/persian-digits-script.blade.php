<script>
    (function () {
        const digitMap = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        const digitRegex = /\d/g;
        const skipTags = new Set(['SCRIPT', 'STYLE', 'TEXTAREA']);
        const attributeList = ['aria-label', 'title', 'placeholder', 'data-label'];
        const attributeSelector = attributeList.map((attr) => `[${attr}]`).join(',');

        const hasSkipFlag = (node) => node?.closest?.('[data-keep-latin-numbers]');
        const toPersianDigits = (value) => value.replace(digitRegex, (digit) => digitMap[digit]);

        /** فقط در صورت تفاوت مقدار را عوض کن؛ وگرنه MutationObserver روی characterData حلقه بی‌نهایت می‌سازد. */
        const setTextNodePersianIfNeeded = (textNode) => {
            if (!textNode || textNode.nodeType !== Node.TEXT_NODE) {
                return;
            }
            const cur = textNode.nodeValue;
            if (!cur) {
                return;
            }
            const next = toPersianDigits(cur);
            if (next !== cur) {
                textNode.nodeValue = next;
            }
        };

        const shouldSkipTextMutationParent = (parent) =>
            !parent || skipTags.has(parent.nodeName) || hasSkipFlag(parent);

        const processAttributes = (element) => {
            attributeList.forEach((attr) => {
                if (!element.hasAttribute(attr)) {
                    return;
                }
                const attrValue = element.getAttribute(attr);
                if (attrValue && digitRegex.test(attrValue)) {
                    const next = toPersianDigits(attrValue);
                    if (next !== attrValue) {
                        element.setAttribute(attr, next);
                    }
                }
            });
        };

        const convertTextNodes = (root) => {
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode(node) {
                    if (!node?.nodeValue || !digitRegex.test(node.nodeValue)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    const parent = node.parentNode;
                    if (!parent) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    if (skipTags.has(parent.nodeName) || hasSkipFlag(parent)) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });

            while (walker.nextNode()) {
                setTextNodePersianIfNeeded(walker.currentNode);
            }
        };

        const processElement = (element) => {
            if (skipTags.has(element?.nodeName) || hasSkipFlag(element)) {
                return;
            }
            convertTextNodes(element);
            processAttributes(element);
        };

        const mutateDigits = (target = document.body) => {
            if (!target) {
                return;
            }

            processElement(target);

            target.querySelectorAll(attributeSelector).forEach((el) => {
                if (hasSkipFlag(el)) {
                    return;
                }
                processAttributes(el);
            });
        };

        const observeMutations = () => {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'characterData') {
                        const parent = mutation.target.parentNode;
                        if (!shouldSkipTextMutationParent(parent)) {
                            setTextNodePersianIfNeeded(mutation.target);
                        }
                        return;
                    }

                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === Node.TEXT_NODE) {
                            if (!shouldSkipTextMutationParent(node.parentNode)) {
                                setTextNodePersianIfNeeded(node);
                            }
                        } else if (node.nodeType === Node.ELEMENT_NODE) {
                            mutateDigits(node);
                        }
                    });
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true,
                characterData: true
            });
        };

        const init = () => {
            mutateDigits();
            observeMutations();
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init, { once: true });
        } else {
            init();
        }
    })();
</script>
