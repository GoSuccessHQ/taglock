/**
 * Status Badge component.
 *
 * Displays a status indicator badge.
 */

import PropTypes from 'prop-types';

/**
 * Status Badge component.
 *
 * @param {Object} props - Component props.
 * @param {string} props.status - Status type ('success' or 'error').
 * @param {string} props.children - Badge text.
 * @param {string} [props.className] - Additional class name.
 * @return {JSX.Element} The status badge.
 */
const StatusBadge = ({ status, children, className = '' }) => {
	const statusClass = status === 'success'
		? 'taglock-admin__status-badge--success'
		: 'taglock-admin__status-badge--error';

	return (
		<span
			className={`taglock-admin__status-badge ${statusClass} ${className}`.trim()}
			role="status"
			aria-live="polite"
		>
			{children}
		</span>
	);
};

StatusBadge.propTypes = {
	status: PropTypes.oneOf(['success', 'error']).isRequired,
	children: PropTypes.node.isRequired,
	className: PropTypes.string,
};

export default StatusBadge;
