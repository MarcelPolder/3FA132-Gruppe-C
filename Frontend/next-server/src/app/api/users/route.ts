import { ApiResponse } from "@/types";
import { NextApiRequest, NextApiResponse } from "next";
import { NextResponse } from "next/server";

async function handler(
	req: NextApiRequest,
	res: NextApiResponse,
) {
	const response = await fetch('http://localhost:8080/rest/users/get', {cache: 'no-cache'});
	if (response.ok) {
		const responseData: ApiResponse = {
			msg: 'Erfolgreich',
			data: await response.json(),
			status: 200,
		}
		return NextResponse.json(responseData);
	} else {
		const responseData: ApiResponse = {
			msg: 'Es konnte keine Verbindung zum Backend aufgebaut werden',
			status: 500,
		}
		return NextResponse.json(responseData);
	}
}

export { handler as GET, handler as POST };