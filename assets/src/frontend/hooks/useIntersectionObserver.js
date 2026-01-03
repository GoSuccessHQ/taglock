/**
 * Intersection Observer Hook.
 *
 * Provides lazy loading functionality using Intersection Observer API.
 *
 * @package TagLock
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';

/**
 * Hook to track if an element is visible in the viewport.
 *
 * @param {Object} options - Intersection observer options.
 * @param {Element|null} options.root - Root element for intersection.
 * @param {string} options.rootMargin - Root margin for intersection.
 * @param {number|number[]} options.threshold - Threshold for intersection.
 * @param {boolean} options.triggerOnce - Whether to trigger only once.
 * @return {[React.RefObject, boolean]} Ref and isVisible state.
 */
const useIntersectionObserver = (options = {}) => {
	const {
		root = null,
		rootMargin = '100px',
		threshold = 0,
		triggerOnce = true,
	} = options;

	const [isVisible, setIsVisible] = useState(false);
	const targetRef = useRef(null);
	const hasTriggered = useRef(false);

	const handleIntersection = useCallback((entries) => {
		const [entry] = entries;
		const isIntersecting = entry?.isIntersecting ?? false;

		if (triggerOnce) {
			if (isIntersecting && !hasTriggered.current) {
				hasTriggered.current = true;
				setIsVisible(true);
			}
		} else {
			setIsVisible(isIntersecting);
		}
	}, [triggerOnce]);

	useEffect(() => {
		const target = targetRef.current;

		// Fallback for browsers without IntersectionObserver.
		if (!('IntersectionObserver' in window)) {
			setIsVisible(true);
			return undefined;
		}

		if (!target) {
			return undefined;
		}

		const observer = new IntersectionObserver(handleIntersection, {
			root,
			rootMargin,
			threshold,
		});

		observer.observe(target);

		return () => {
			observer.disconnect();
		};
	}, [root, rootMargin, threshold, handleIntersection]);

	return [targetRef, isVisible];
};

export default useIntersectionObserver;
