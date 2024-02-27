// Styles
import { Navigation } from '@/components/Navigation';
import classes from '@/styles/global/classes.module.scss';
import '@/styles/layout/pages.scss';

export default function pagesLayout({children}: {children: React.ReactNode}) {
	return (
		<>
			<header>
				<div className={classes.inner}>
					<Navigation />
				</div>
			</header>
			<main>
				<article className={classes.inner}>
					{children}
				</article>
			</main>
			<footer>
				<div className={classes.inner}>
					<p>Copyright © 2023</p>
					<div id='authors'>
						<span>M. Kirchermeier,</span>
						<span>M. Krug,</span>
						<span>M. Polder,</span>
						<span>O. Fuchs</span>
					</div>
				</div>
			</footer>
		</>
	)
}