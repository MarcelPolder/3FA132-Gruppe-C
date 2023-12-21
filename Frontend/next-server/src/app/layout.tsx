import type { Metadata } from 'next'
import { Roboto } from 'next/font/google';

// Styles
import '@/styles/theme/theme.css';
import '@/styles/global/reset.scss';
import '@/styles/global/layout.scss';

const inter = Roboto({
	subsets: ['latin'],
	weight: "400"
});

export const metadata: Metadata = {
	title: 'Hausverwaltung - Gruppe 3',
	description: 'Abschlussprojekt in der Berufsschule',
}

export default function RootLayout({
	children,
}: {
	children: React.ReactNode
}) {
	return (
		<html lang="de">
			<body className={inter.className}>
				{children}
			</body>
		</html>
	)
}
