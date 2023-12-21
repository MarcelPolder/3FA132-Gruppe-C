// Styles
import '@/styles/layout/auth.scss';

export default function authLayout({
	children,
  }: {
	children: React.ReactNode
}) {
	return (
		<main>
			<article>
				{children}
			</article>
		</main>
	);
}