/**
 * Pro Badge component.
 *
 * Displays a badge linking to the Pro upgrade page.
 */

import PropTypes from 'prop-types';
import { useAdminConfig } from '../hooks';

/**
 * Pro Badge component.
 *
 * @param {Object} props - Component props.
 * @param {boolean} [props.inline=true] - Whether to display inline.
 * @return {JSX.Element} The Pro badge.
 */
const ProBadge = ({ inline = true }) => {
	const { proUrl } = useAdminConfig();

	const className = inline
		? 'taglock-admin__pro-badge taglock-admin__pro-badge--inline'
		: 'taglock-admin__pro-badge';

	return (
		<a
			className={className}
			href={proUrl}
			target="_blank"
			rel="noopener noreferrer"
		>
			PRO
		</a>
	);
};

ProBadge.propTypes = {
	inline: PropTypes.bool,
};

export default ProBadge;
