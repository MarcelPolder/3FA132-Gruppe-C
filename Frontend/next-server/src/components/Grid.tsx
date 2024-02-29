import { ReactNode } from "react";
import styles from '@/styles/components/grid.module.scss';

type GridProps = {
	children: ReactNode;
	columnsM?: number;
	columnsTP?: number;
	columnsTL?: number;
	columnsD?: number;
	gap?: number;
}

export default function Grid({ children, columnsM, columnsTP, columnsTL, columnsD, gap }: GridProps) {
	const columnsMobile = typeof columnsM === 'undefined' ? 12 : columnsM;
	const columnsTabletP = typeof columnsTP === 'undefined' ? columnsMobile : columnsTP;
	const columnsTabletL = typeof columnsTL === 'undefined' ? columnsTabletP : columnsTL;
	const columnsDesktop = typeof columnsD === 'undefined' ? columnsTabletL : columnsD;
	const gridgap = typeof gap === 'undefined' ? 24 : gap;

	return (
		<div className={`${styles.grid} ${styles[`col-${columnsMobile}`]} ${styles[`col-${columnsTabletP}-tp`]} ${styles[`col-${columnsTabletL}-tl`]} ${styles[`col-${columnsTabletL}-d`]} ${styles[`gap-${gridgap}`]}`}>
			{ children }
		</div>
	);
}