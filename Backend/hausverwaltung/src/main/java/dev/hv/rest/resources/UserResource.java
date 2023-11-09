package dev.hv.rest.resources;

import dev.hv.db.model.IDUser;
import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;
import dev.hv.rest.util.UserJsonUtil;

import java.util.List;

import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.FormParam;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
import jakarta.ws.rs.PUT;
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
		return Response.status(Response.Status.OK).entity(user).build();
	}

	@GET
	@Path("get")
	@Produces(MediaType.APPLICATION_JSON)
	public Response getAllUsers() {
		List<IRUser> users = (new UserJsonUtil()).getAll();
		return Response.status(Response.Status.OK).entity(users).build();
	}

	@DELETE
	@Path("delete/{id}")
	@Produces(MediaType.APPLICATION_JSON)
	public Response deleteUser(@PathParam("id") int id) {
		UserJsonUtil util = new UserJsonUtil();
		util.delete(id);
		return Response.status(Response.Status.OK).build();
	}
	
	@PUT
	@Path("create")
	@Consumes(MediaType.APPLICATION_FORM_URLENCODED)
	@Produces(MediaType.APPLICATION_JSON)
	public Response createUser(
		@FormParam("id") int id,
		@FormParam("firstname") String firstname,
		@FormParam("lastname") String lastname,
		@FormParam("password") String password,
		@FormParam("token") String token
	) {
		RUser user = new RUser(firstname, id, lastname, password, password);
		UserJsonUtil util = new UserJsonUtil();
		System.out.println(user.getId());
		int created = util.insert(user);
		if (created > 0) {
			return Response.status(Response.Status.CREATED).entity(user).build();
		}
		return Response.status(Response.Status.INTERNAL_SERVER_ERROR).build();
	}

	@POST
	@Path("update/{id}")
	@Consumes(MediaType.APPLICATION_FORM_URLENCODED)
	@Produces(MediaType.APPLICATION_JSON)
	public Response updateUser(
		@PathParam("id") int id,
		@FormParam("firstname") String firstname,
		@FormParam("lastname") String lastname,
		@FormParam("password") String password,
		@FormParam("token") String token
	) {
		UserJsonUtil util = new UserJsonUtil();
		IRUser user = util.getWithID(id);
		if (firstname != null) user.setFirstname(firstname);
		if (lastname != null) user.setLastname(lastname);
		if (password != null) user.setPassword(password);
		if (token != null) user.setToken(token);
		util.update(user);
		return Response.status(Response.Status.OK).entity(user).build();
	}
}