import { NextApiRequest, NextApiResponse } from "next";
import { NextResponse } from "next/server";

async function handler(
	req: NextApiRequest,
	res: NextApiResponse,
) {
	const response = await fetch('http://localhost:8080/rest/users/get', {cache: 'no-cache'});
	if (response.ok) {
		return NextResponse.json(await response.json());
	} else {
		return NextResponse.json({error: 'Es konnte keine Verbindung zum Backend aufgebaut werden', status: 500});
	}
}

export { handler as GET, handler as POST };