package dev.hv.rest.model;

import com.fasterxml.jackson.annotation.JsonProperty;
import com.fasterxml.jackson.annotation.JsonTypeInfo;
import com.fasterxml.jackson.annotation.JsonTypeName;

import dev.hv.db.model.IDUser;
import lombok.AllArgsConstructor;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import lombok.ToString;

@Getter
@Setter
@NoArgsConstructor
@AllArgsConstructor
@ToString
@JsonTypeInfo(include = JsonTypeInfo.As.WRAPPER_OBJECT, use = JsonTypeInfo.Id.NAME)
@JsonTypeName(value = "user")
public class RUser implements IRUser {

	@JsonProperty(value = "id")
	Integer Id;
	
	@JsonProperty(value = "firstname")
	String Firstname;

	@JsonProperty(value = "lastname")
	String Lastname;

	@JsonProperty(value = "password")
	String Password;

	@JsonProperty(value = "token")
	String Token;
	
	public RUser(IDUser dbUser) {
		setId(dbUser.getId());
		setFirstname(dbUser.getFirstname());
		setLastname(dbUser.getLastname());
		setPassword(dbUser.getPassword());
		setToken(dbUser.getToken());
	}
}
