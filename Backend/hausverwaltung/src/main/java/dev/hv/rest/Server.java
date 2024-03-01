package dev.hv.rest;

import java.io.FileInputStream;
import java.io.IOException;
import java.net.URI;
import java.util.Properties;

import org.glassfish.jersey.jdkhttp.JdkHttpServerFactory;
import org.glassfish.jersey.server.ResourceConfig;

import com.sun.net.httpserver.HttpServer;
import sun.misc.Signal;

public class Server {

	ResourceConfig resources;
	String url;
	Properties appProps;
	HttpServer httpServer;

	public Server() {
		appProps = new Properties();
		try {
			appProps.load(new FileInputStream(getClass().getResource("/app.properties").getPath()));
		} catch (IOException e) {
			e.printStackTrace();
		}
		resources = new ResourceConfig().packages(appProps.getProperty("API_RESOURCES"));
		url = "http://"+appProps.getProperty("API_HOST")+":"+appProps.getProperty("API_PORT")+"/";
	}

	public boolean open() {
		httpServer = JdkHttpServerFactory.createHttpServer(URI.create(this.url), this.resources);
		System.out.println("The server is now listening on "+this.url);
		return true;
	}

	public boolean close() {
		this.httpServer.stop(0);
		System.out.println("The server is now closed.");
		return true;
	}
	
	public static void main (String[] args) {
		Server server = new Server();
		server.open();
		Signal.handle(new Signal("INT"), signal -> server.close());
	}
}
