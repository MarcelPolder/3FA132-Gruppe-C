package dev.hv.rest.resources;

import dev.hv.rest.model.IRUser;
import dev.hv.rest.model.RUser;
import dev.hv.rest.util.UserJsonUtil;

import java.util.List;

import org.jdbi.v3.core.statement.UnableToExecuteStatementException;

import jakarta.ws.rs.Consumes;
import jakarta.ws.rs.DELETE;
import jakarta.ws.rs.FormParam;
import jakarta.ws.rs.GET;
import jakarta.ws.rs.POST;
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
		try {
			IRUser user = (new UserJsonUtil()).getWithID(id);
			return Response.status(Response.Status.OK).entity(user).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT).entity("There is no user with id " + id + ".").build();
		}
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

	@POST
	@Path("create")
	@Consumes(MediaType.APPLICATION_JSON)
	@Produces(MediaType.APPLICATION_JSON)
	public Response createUser(RUser user) {
		UserJsonUtil util = new UserJsonUtil();
		System.out.println(user.getId());
		try {
			int created = util.insert(user);
			if (created > 0) {
				return Response.status(Response.Status.CREATED).entity(user).build();
			}
			return Response.status(Response.Status.INTERNAL_SERVER_ERROR)
					.entity("There was an Error inserting the user into the database!").build();
		} catch (UnableToExecuteStatementException ex) {
			return Response.status(Response.Status.CONFLICT).entity("A User with this ID already exists").build();
		}
		catch (Exception ex) {
			return Response.status(Response.Status.CONFLICT)
					.entity("There was an Error inserting the user into the database! Details: " + ex.getMessage()).build();
		}

	}

	@POST
	@Path("update/{id}")
	@Consumes(MediaType.APPLICATION_FORM_URLENCODED)
	@Produces(MediaType.APPLICATION_JSON)
	public Response updateUser(@PathParam("id") int id, @FormParam("firstname") String firstname,
			@FormParam("lastname") String lastname, @FormParam("password") String password,
			@FormParam("token") String token) {
		UserJsonUtil util = new UserJsonUtil();
		try {
			IRUser user = util.getWithID(id);
			if (firstname != null)
				user.setFirstname(firstname);
			if (lastname != null)
				user.setLastname(lastname);
			if (password != null)
				user.setPassword(password);
			if (token != null)
				user.setToken(token);
			util.update(user);
			return Response.status(Response.Status.OK).entity(user).build();
		} catch (NullPointerException eNullPointerException) {
			return Response.status(Response.Status.NO_CONTENT)
					.entity("There is no user with the id " + id + " to update!").build();
		}
	}

	@POST
	@Path("authenticate")
	@Consumes("application/x-www-form-urlencoded")
	@Produces(MediaType.APPLICATION_JSON)
	public Response authenticateUser(
		@FormParam("username") String username,
		@FormParam("password") String password
	) {
		UserJsonUtil util = new UserJsonUtil();
		// TODO: Implement
		for (int i = 0; i>1 ; i++) {
			if (username != null) {
				IRUser user = util.getWithID(i);
				String fullUser = user.getFirstname().concat(user.getLastname());
				if (fullUser == username) {
					if (password == user.getPassword()) {
						return Response.status(Response.Status.OK).entity(true).build();
					} else {
						return Response.status(Response.Status.UNAUTHORIZED).entity(fullUser).build();
					}
				} else {
					return Response.status(Response.Status.NO_CONTENT).entity("There is no user with the username " + fullUser + " to update!").build();
				}
			}
		}
		//else 403	
		return Response.status(Response.Status.OK).entity(true).build();
	}
}