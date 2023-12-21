// Styles
import SignOutButton from '@/components/SignOutButton';
import classes from '@/styles/global/classes.module.scss';
import '@/styles/layout/pages.scss';

export default function pagesLayout({children}: {children: React.ReactNode}) {
	return (
		<>
			<header>
				<div className={classes.inner}>
					<nav>
						<ul>
							<li>
								<a href="/">
									<h1>HV</h1>
								</a>
							</li>
							<li>
								<a href="/kunden">Kunden</a>
							</li>
						</ul>
						<ul>
							<li>
								<SignOutButton/>
							</li>
						</ul>
					</nav>
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