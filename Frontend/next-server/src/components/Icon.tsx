import { IconDefinition, fa0 } from "@fortawesome/free-solid-svg-icons"
import styles from '@/styles/components/icon.module.scss';
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";

export interface IconProps {
	icon: IconDefinition;
	marginR?: number;
	marginL?: number;
}

export default function Icon({icon, marginR, marginL}: IconProps) {

	const marginRclass = marginR == null ? "" : styles[`mr${marginR}`];
	const marginLclass = marginL == null ? "" : styles[`ml${marginL}`];

	return (
		<span className={`${styles.icon} ${marginRclass} ${marginLclass}`}>
			<FontAwesomeIcon icon={icon} />
		</span>
	);
}