export interface ApiResponse {
	status: number;
	msg: string;
	data?: any;
}

export interface CustomerData {
	id?: number;
	firstname: string;
	lastname: string;
}