package dev.hv.rest.resources;

import dev.hv.db.model.IDUser;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.util.UserJsonUtil;

import java.util.List;

import jakarta.ws.rs.GET;
import jakarta.ws.rs.Path;
import jakarta.ws.rs.PathParam;
import jakarta.ws.rs.Produces;
import jakarta.ws.rs.core.MediaType;
import jakarta.ws.rs.core.Response;

@Path("users")
public class UserResource {
	@GET
	@Path("get/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getUserById(@PathParam("id") int id) {
		IRUser user = (new UserJsonUtil()).getWithID(id);
		return Response.status(200).entity(user).build();
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllUsers() {
		List<IRUser> users = (new UserJsonUtil()).getAll();
		return Response.status(200).entity(users).build();
	}
}