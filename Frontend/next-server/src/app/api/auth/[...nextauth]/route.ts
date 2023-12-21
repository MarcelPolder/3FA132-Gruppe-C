import NextAuth, { NextAuthOptions } from 'next-auth';
import CredentialsProvider from 'next-auth/providers/credentials';
import axios from 'axios';

export const authOptions: NextAuthOptions = {
	providers: [
		CredentialsProvider({
			name: "credentials",
			credentials: {
				username: {
					label: "Benutzer",
					type: "text",
					placeholder: "john.doe"
				},
				password: {
					label: "Passwort",
					type: "password"
				},
			},
			async authorize(credentials, req) {
				if (typeof credentials !== 'undefined') {
					const username: string = credentials.username;
					const password: string = credentials.password;

					const auth = await axios.post("http://localhost:8080/rest/users/authenticate", {
						username: username,
						password: password,
					}, {
						headers: { 'Content-Type': 'application/x-www-form-urlencoded'}
					});
					if (auth.status == 200) {
						return {
							id: "1",
							username: username,
						};
					}
				}
				return null;
			},
			id: 'credentials',
			type: 'credentials'
			
		})
	],
	pages: {
		signIn: '/auth/login',
		error: '/auth/login',
	}
	
}
const handler = NextAuth(authOptions);
export { handler as GET, handler as POST };