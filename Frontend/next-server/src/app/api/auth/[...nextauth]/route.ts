import NextAuth, { NextAuthOptions } from 'next-auth';
import CredentialsProvider from 'next-auth/providers/credentials';

export const authOptions: NextAuthOptions = {
	providers: [
		CredentialsProvider({
			name: "Credentials",
			credentials: {
				username: {label: "Benutzer", type: "text", placeholder: "john.doe"},
				password: {label: "Passwort", type: "password"},
			},
			async authorize(credentials, req) {
				// TODO: Get user
				return null;
			},
			
		})
	],
	
}
const handler = NextAuth(authOptions);
export { handler as GET, handler as POST };