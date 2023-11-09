package dev.hv.rest;

import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;
import jakarta.ws.rs.client.Client;
import jakarta.ws.rs.client.ClientBuilder;
import jakarta.ws.rs.client.Entity;
import jakarta.ws.rs.client.WebTarget;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

public class RestClient {

	WebTarget target;
	Client client;

	public RestClient() {
		client = ClientBuilder.newClient();
		target = client.target("http://localhost:8080/rest");
	}

	public RUser getUser(String id) {
		Response response = target.path("users").path("get").path(id).request(MediaType.APPLICATION_JSON).get();
		RUser user = response.readEntity(RUser.class);
		return user;
	}

	public int createUser(RUser user) {
		Entity<RUser> entity = Entity.entity(user, MediaType.APPLICATION_JSON);
		Response response = target.path("users").path("create").request(MediaType.APPLICATION_JSON).post(entity);
		return response.getStatus();
	}

	public static void main(String[] args) {
		RestClient c = new RestClient();
		System.out.println(c.createUser(new RUser(1, "Moritz", "Kirchermeier", "Passwort", "Token")));
	}
	
}